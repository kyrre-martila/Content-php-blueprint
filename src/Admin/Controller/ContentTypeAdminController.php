<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Domain\Content\ContentType;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\View\TemplateRenderer;
use RuntimeException;

final class ContentTypeAdminController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session,
        private readonly string $projectRoot,
    ) {
    }

    public function index(Request $request): Response
    {
        $types = $this->contentTypes->findAll();

        $rows = array_map(
            fn (ContentType $type): array => $this->buildRow($type),
            $types
        );

        $html = $this->templateRenderer->renderTemplate(
            'admin/content-types/index.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'rows' => $rows,
                'success' => $this->session->pullFlash('content_type_success'),
                'error' => $this->session->pullFlash('content_type_error'),
            ]
        );

        return Response::html($html);
    }

    public function destroy(Request $request): Response
    {
        if (!$this->authSession->isAuthenticated()) {
            return Response::json(['success' => false], 401);
        }

        if (!$this->isDeleteMethod($request) || !$this->hasValidCsrfToken($request)) {
            return Response::json(['success' => false], 400);
        }

        $slug = $request->attribute('slug');

        if (!is_string($slug) || $slug === '') {
            return Response::json(['success' => false], 404);
        }

        $contentType = $this->contentTypes->findByName($slug);

        if ($contentType === null) {
            return Response::json(['success' => false], 404);
        }

        if (!$this->canDelete($contentType)) {
            $this->session->flash('content_type_error', 'This content type is protected and cannot be deleted.');

            return Response::redirect('/admin/content-types');
        }

        try {
            $this->contentTypes->remove($contentType);
        } catch (RuntimeException) {
            $this->session->flash('content_type_error', 'Unable to delete content type. It may still have content items.');

            return Response::redirect('/admin/content-types');
        }

        $this->session->flash('content_type_success', sprintf('Content type "%s" deleted.', $contentType->label()));

        return Response::redirect('/admin/content-types');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRow(ContentType $type): array
    {
        $templatePath = $type->isCollectionView()
            ? sprintf('collections/%s.php', $type->name())
            : sprintf('content/%s.php', $type->name());

        return [
            'name' => $type->label(),
            'slug' => $type->name(),
            'viewType' => $type->viewType()->value,
            'template' => $templatePath,
            'templateExists' => $this->templateExists($templatePath),
            'canDelete' => $this->canDelete($type),
            'editPath' => '/admin/content-types/' . rawurlencode($type->name()) . '/edit',
            'deletePath' => '/admin/content-types/' . rawurlencode($type->name()),
        ];
    }

    private function templateExists(string $template): bool
    {
        return is_file($this->projectRoot . '/templates/' . ltrim($template, '/'));
    }

    private function canDelete(ContentType $type): bool
    {
        return $type->name() !== 'page';
    }

    private function isDeleteMethod(Request $request): bool
    {
        if ($request->method() === 'DELETE') {
            return true;
        }

        $methodOverride = $request->postParams()['_method'] ?? null;

        return is_string($methodOverride) && strtoupper($methodOverride) === 'DELETE';
    }

    private function hasValidCsrfToken(Request $request): bool
    {
        $sessionToken = $this->session->get('_csrf_token');

        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        $submittedToken = $request->postParams()['_csrf_token'] ?? null;

        return is_string($submittedToken) && hash_equals($sessionToken, $submittedToken);
    }
}
