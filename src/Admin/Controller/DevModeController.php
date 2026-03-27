<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Editor\DevMode;
use App\Infrastructure\Editor\EditableFileRegistry;
use App\Infrastructure\Editor\EditHistoryLogger;
use App\Infrastructure\Logging\Logger;
use App\Infrastructure\View\TemplateRenderer;

final class DevModeController
{
    private const MAX_EDITABLE_BYTES = 262144;

    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session,
        private readonly DevMode $devMode,
        private readonly EditableFileRegistry $fileRegistry,
        private readonly EditHistoryLogger $editHistory,
        private readonly Logger $logger
    ) {
    }

    public function enable(Request $request): Response
    {
        if (!$this->devMode->canUse()) {
            return Response::redirect('/admin');
        }

        $this->devMode->enable();
        $this->session->flash('success', 'Dev Mode enabled.');

        return Response::redirect($this->redirectTarget($request));
    }

    public function disable(Request $request): Response
    {
        if (!$this->devMode->canUse()) {
            return Response::redirect('/admin');
        }

        $this->devMode->disable();
        $this->session->flash('success', 'Dev Mode disabled.');

        return Response::redirect($this->redirectTarget($request));
    }

    public function index(Request $request): Response
    {
        if (!$this->devMode->canUse()) {
            return Response::redirect('/admin');
        }

        $html = $this->renderer->render(
            dirname(__DIR__, 3) . '/templates/admin/dev-mode/index.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'devModeActive' => $this->devMode->isActive(),
                'editableFiles' => $this->fileRegistry->discover(),
                'allowedRoots' => $this->devMode->allowedRoots(),
                'success' => $this->session->pullFlash('success'),
                'error' => $this->session->pullFlash('error'),
            ]
        );

        return Response::html($html);
    }

    public function edit(Request $request): Response
    {
        if (!$this->devMode->canUse()) {
            return Response::redirect('/admin');
        }

        if (!$this->devMode->isActive()) {
            $this->session->flash('error', 'Enable Dev Mode before editing files.');

            return Response::redirect('/admin/dev-mode');
        }

        $path = $this->requestedPath($request);

        if ($path === null) {
            $this->logRejectedAttempt('invalid_path', null);
            $this->session->flash('error', 'Invalid file path.');

            return Response::redirect('/admin/dev-mode');
        }

        $absolutePath = $this->fileRegistry->absolutePath($path);

        if ($absolutePath === null || !$this->isSafeEditableFile($absolutePath, $path)) {
            $this->logRejectedAttempt('path_not_allowed', $path);
            $this->session->flash('error', 'Selected file is not editable in Dev Mode.');

            return Response::redirect('/admin/dev-mode');
        }

        $contents = file_get_contents($absolutePath);

        if (!is_string($contents)) {
            $this->session->flash('error', 'Failed to read selected file.');

            return Response::redirect('/admin/dev-mode');
        }

        $html = $this->renderer->render(
            dirname(__DIR__, 3) . '/templates/admin/dev-mode/edit.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'devModeActive' => $this->devMode->isActive(),
                'relativePath' => $path,
                'content' => $contents,
                'maxSize' => self::MAX_EDITABLE_BYTES,
                'success' => $this->session->pullFlash('success'),
                'error' => $this->session->pullFlash('error'),
            ]
        );

        return Response::html($html);
    }

    public function update(Request $request): Response
    {
        if (!$this->devMode->canUse()) {
            return Response::redirect('/admin');
        }

        if (!$this->devMode->isActive()) {
            $this->session->flash('error', 'Enable Dev Mode before saving files.');

            return Response::redirect('/admin/dev-mode');
        }

        $post = $request->postParams();
        $path = isset($post['path']) && is_scalar($post['path']) ? (string) $post['path'] : null;
        $newContents = isset($post['content']) && is_scalar($post['content']) ? (string) $post['content'] : null;

        if ($path === null || $newContents === null) {
            $this->logRejectedAttempt('invalid_payload', $path);
            $this->session->flash('error', 'Invalid save payload.');

            return Response::redirect('/admin/dev-mode');
        }

        $absolutePath = $this->fileRegistry->absolutePath($path);

        if ($absolutePath === null || !$this->isSafeEditableFile($absolutePath, $path)) {
            $this->logRejectedAttempt('path_not_allowed', $path);
            $this->session->flash('error', 'Selected file is not editable in Dev Mode.');

            return Response::redirect('/admin/dev-mode');
        }

        if (strlen($newContents) > self::MAX_EDITABLE_BYTES) {
            $this->logRejectedAttempt('file_too_large', $path);
            $this->session->flash('error', 'File content exceeds size limit.');

            return Response::redirect('/admin/dev-mode/edit?path=' . rawurlencode($path));
        }

        $hashBefore = is_file($absolutePath) ? hash_file('sha256', $absolutePath) : null;

        if (!is_string($hashBefore) && $hashBefore !== null) {
            $hashBefore = null;
        }

        if (!$this->writeAtomically($absolutePath, $newContents)) {
            $this->logRejectedAttempt('write_failed', $path);
            $this->session->flash('error', 'Unable to save file.');

            return Response::redirect('/admin/dev-mode/edit?path=' . rawurlencode($path));
        }

        $hashAfter = hash_file('sha256', $absolutePath);

        if (!is_string($hashAfter)) {
            $hashAfter = hash('sha256', $newContents);
        }

        $this->editHistory->logUpdate($this->authSession->user(), $path, $hashBefore, $hashAfter);

        $this->logger->info('Dev Mode file updated.', [
            'path' => $path,
            'user_id' => $this->authSession->user()['id'] ?? null,
            'user_email' => $this->authSession->user()['email'] ?? null,
        ], 'dev_mode');

        $this->session->flash('success', 'File saved successfully.');

        return Response::redirect('/admin/dev-mode/edit?path=' . rawurlencode($path));
    }

    private function requestedPath(Request $request): ?string
    {
        $query = $request->queryParams();

        if (!isset($query['path']) || !is_scalar($query['path'])) {
            return null;
        }

        $path = trim((string) $query['path']);

        return $path === '' ? null : $path;
    }

    private function isSafeEditableFile(string $absolutePath, string $relativePath): bool
    {
        if (!$this->fileRegistry->isSupportedPath($relativePath)) {
            return false;
        }

        if (!is_file($absolutePath) || !is_readable($absolutePath) || !is_writable($absolutePath)) {
            return false;
        }

        $size = filesize($absolutePath);

        if (!is_int($size) || $size > self::MAX_EDITABLE_BYTES) {
            return false;
        }

        return true;
    }

    private function writeAtomically(string $path, string $content): bool
    {
        $directory = dirname($path);
        $tempPath = sprintf('%s/.devmode-%s.tmp', $directory, uniqid('', true));

        $written = file_put_contents($tempPath, $content, LOCK_EX);

        if ($written === false) {
            return false;
        }

        $renamed = rename($tempPath, $path);

        if (!$renamed) {
            @unlink($tempPath);

            return false;
        }

        return true;
    }

    private function redirectTarget(Request $request): string
    {
        $referer = $request->serverParams()['HTTP_REFERER'] ?? null;

        if (!is_string($referer) || trim($referer) === '') {
            return '/admin';
        }

        $path = parse_url($referer, PHP_URL_PATH);

        if (!is_string($path) || trim($path) === '') {
            return '/admin';
        }

        $query = parse_url($referer, PHP_URL_QUERY);

        if (!is_string($query) || trim($query) === '') {
            return $path;
        }

        return $path . '?' . $query;
    }

    private function logRejectedAttempt(string $reason, ?string $path): void
    {
        $user = $this->authSession->user();

        $this->logger->warning('Dev Mode edit rejected.', [
            'reason' => $reason,
            'path' => $path,
            'user_id' => is_array($user) ? ($user['id'] ?? null) : null,
            'user_email' => is_array($user) ? ($user['email'] ?? null) : null,
        ], 'dev_mode');
    }
}
