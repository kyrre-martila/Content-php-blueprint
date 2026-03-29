<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Application\DevMode\DevFileService;
use App\Domain\Content\ContentType;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Domain\Logging\LoggerInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Editor\EditableFileRegistry;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class TemplateAdminController
{
    private const MAX_TEMPLATE_BYTES = 262144;

    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session,
        private readonly TemplateResolver $templateResolver,
        private readonly EditableFileRegistry $fileRegistry,
        private readonly DevFileService $devFileService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function index(Request $request): Response
    {
        $contentTypes = $this->contentTypes->findAll();

        $html = $this->templateRenderer->renderTemplate(
            'admin/templates/index.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'templateGroups' => [
                    'Index' => $this->buildIndexTemplates(),
                    'Content' => $this->buildContentTemplates($contentTypes),
                    'Collections' => $this->buildCollectionTemplates($contentTypes),
                    'System' => $this->buildSystemTemplates(),
                ],
            ]
        );

        return Response::html($html);
    }

    public function edit(Request $request): Response
    {
        $path = $this->requestedPath($request);

        if ($path === null || !$this->isEditableTemplatePath($path)) {
            $this->logRejectedAttempt('invalid_path', $path);
            $this->session->flash('error', 'Invalid template path.');

            return Response::redirect('/admin/templates');
        }

        $exists = $this->templateResolver->templateExists($path);
        $content = '';

        if ($exists) {
            try {
                $content = $this->devFileService->safeReadFile($path);
            } catch (\RuntimeException) {
                $this->session->flash('error', 'Unable to load selected template file.');

                return Response::redirect('/admin/templates');
            }
        }

        $html = $this->templateRenderer->renderTemplate(
            'admin/templates/edit.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'templatePath' => $path,
                'templateName' => basename($path),
                'content' => $content,
                'templateExists' => $exists,
                'maxSize' => self::MAX_TEMPLATE_BYTES,
                'success' => $this->session->pullFlash('success'),
                'error' => $this->session->pullFlash('error'),
            ]
        );

        return Response::html($html);
    }

    public function update(Request $request): Response
    {
        $post = $request->postParams();
        $path = isset($post['path']) && is_scalar($post['path']) ? trim((string) $post['path']) : null;
        $content = isset($post['content']) && is_scalar($post['content']) ? (string) $post['content'] : null;
        $createIfMissing = isset($post['create_if_missing']) && (string) $post['create_if_missing'] === '1';

        if ($path === null || $content === null || $path === '' || !$this->isEditableTemplatePath($path)) {
            $this->logRejectedAttempt('invalid_payload', $path);
            $this->session->flash('error', 'Invalid template update payload.');

            return Response::redirect('/admin/templates');
        }

        if (strlen($content) > self::MAX_TEMPLATE_BYTES) {
            $this->session->flash('error', 'Template content exceeds size limit.');

            return Response::redirect('/admin/templates/edit?path=' . rawurlencode($path));
        }

        $exists = $this->templateResolver->templateExists($path);

        if (!$exists && !$createIfMissing) {
            $this->session->flash('error', 'Template file does not exist. Select create template file to create it.');

            return Response::redirect('/admin/templates/edit?path=' . rawurlencode($path));
        }

        $hashBefore = null;
        if ($exists) {
            try {
                $absolutePath = $this->devFileService->absolutePath($path);
            } catch (\RuntimeException) {
                $this->session->flash('error', 'Template path is not editable.');

                return Response::redirect('/admin/templates');
            }

            $hashBefore = hash_file('sha256', $absolutePath);
            if (!is_string($hashBefore)) {
                $hashBefore = null;
            }
        }

        try {
            if ($exists) {
                $this->devFileService->safeWriteFile($path, $content);
            } else {
                $this->devFileService->safeCreateFile($path, $content);
            }
        } catch (\RuntimeException) {
            $this->logRejectedAttempt('write_failed', $path);
            $this->session->flash('error', 'Unable to save template file.');

            return Response::redirect('/admin/templates/edit?path=' . rawurlencode($path));
        }

        try {
            $absolutePath = $this->devFileService->absolutePath($path);
            $hashAfter = hash_file('sha256', $absolutePath);
        } catch (\RuntimeException) {
            $hashAfter = hash('sha256', $content);
        }

        if (!is_string($hashAfter)) {
            $hashAfter = hash('sha256', $content);
        }

        $this->logger->info('Template file updated via Template Manager.', [
            'path' => $path,
            'created' => !$exists,
            'hash_before' => $hashBefore,
            'hash_after' => $hashAfter,
            'user_id' => $this->authSession->user()['id'] ?? null,
            'user_email' => $this->authSession->user()['email'] ?? null,
        ], 'dev_mode');

        $this->session->flash('success', $exists ? 'Template saved successfully.' : 'Template created successfully.');

        return Response::redirect('/admin/templates/edit?path=' . rawurlencode($path));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildIndexTemplates(): array
    {
        return [
            $this->createTemplateEntry(
                'Default index template',
                'Index',
                'templates/index.php',
                null,
                true,
            ),
        ];
    }

    /**
     * @param list<ContentType> $contentTypes
     * @return list<array<string, mixed>>
     */
    private function buildContentTemplates(array $contentTypes): array
    {
        $entries = [];

        foreach ($contentTypes as $contentType) {
            $entries[] = $this->createTemplateEntry(
                $contentType->label(),
                'Content',
                sprintf('templates/content/%s.php', $contentType->name()),
                'templates/index.php'
            );
        }

        return $entries;
    }

    /**
     * @param list<ContentType> $contentTypes
     * @return list<array<string, mixed>>
     */
    private function buildCollectionTemplates(array $contentTypes): array
    {
        $entries = [];

        foreach ($contentTypes as $contentType) {
            if (!$contentType->isCollectionView()) {
                continue;
            }

            $entries[] = $this->createTemplateEntry(
                $contentType->label(),
                'Collection',
                sprintf('templates/collections/%s.php', $contentType->name()),
                'templates/system/404.php'
            );
        }

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildSystemTemplates(): array
    {
        return [
            $this->createTemplateEntry('404', 'System', 'templates/system/404.php', null),
            $this->createTemplateEntry('Search', 'System', 'templates/system/search.php', 'templates/system/404.php'),
        ];
    }

    private function createTemplateEntry(
        string $name,
        string $type,
        string $path,
        ?string $fallbackPath,
        bool $isFallbackRole = false,
    ): array {
        $exists = $this->templateResolver->templateExists($path);
        $fallbackExists = $fallbackPath !== null ? $this->templateResolver->templateExists($fallbackPath) : false;

        $status = 'missing';
        if ($exists) {
            $status = 'exists';
        } elseif ($fallbackPath !== null && $fallbackExists) {
            $status = 'fallback';
        }

        return [
            'name' => $name,
            'type' => $type,
            'path' => $path,
            'status' => $status,
            'fallbackPath' => $fallbackPath,
            'isFallbackRole' => $isFallbackRole,
            'editPath' => '/admin/templates/edit?path=' . rawurlencode($path),
        ];
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

    private function isEditableTemplatePath(string $path): bool
    {
        if (!str_starts_with(ltrim($path, '/'), 'templates/')) {
            return false;
        }

        if (!$this->fileRegistry->isSupportedPath($path)) {
            return false;
        }

        return $this->devFileService->isAllowedPath($path);
    }

    private function logRejectedAttempt(string $reason, ?string $path): void
    {
        $user = $this->authSession->user();

        $this->logger->warning('Template editor request rejected.', [
            'reason' => $reason,
            'path' => $path,
            'user_id' => is_array($user) ? ($user['id'] ?? null) : null,
            'user_email' => is_array($user) ? ($user['email'] ?? null) : null,
        ], 'dev_mode');
    }
}
