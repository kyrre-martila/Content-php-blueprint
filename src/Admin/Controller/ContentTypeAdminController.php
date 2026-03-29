<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Domain\Content\ContentType;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Exception\InvalidContentTypeException;
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

    public function create(Request $request): Response
    {
        return $this->renderForm(
            $request,
            'admin/content-types/create.php',
            [
                'errors' => [],
                'old' => [
                    'name' => '',
                    'slug' => '',
                    'view_type' => ContentViewType::SINGLE->value,
                ],
            ]
        );
    }

    public function store(Request $request): Response
    {
        $post = $request->postParams();
        $input = $this->extractInput($post);

        $errors = $this->validateInput($input, true);

        if ($errors !== []) {
            return $this->renderForm(
                $request,
                'admin/content-types/create.php',
                [
                    'errors' => $errors,
                    'old' => $input,
                ],
                422
            );
        }

        $contentType = $this->buildContentType($input);

        if ($contentType === null) {
            return $this->renderForm(
                $request,
                'admin/content-types/create.php',
                [
                    'errors' => ['general' => 'Unable to save content type. Please verify the entered values.'],
                    'old' => $input,
                ],
                422
            );
        }

        $this->contentTypes->save($contentType);
        $this->session->flash('content_type_success', sprintf('Content type "%s" created.', $contentType->label()));

        return Response::redirect('/admin/content-types');
    }

    public function edit(Request $request): Response
    {
        $identifier = $this->resolveIdentifier($request);

        if ($identifier === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $contentType = $this->contentTypes->findByName($identifier);

        if ($contentType === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        return $this->renderForm(
            $request,
            'admin/content-types/edit.php',
            [
                'contentType' => $contentType,
                'errors' => [],
                'old' => [
                    'name' => $contentType->label(),
                    'slug' => $contentType->name(),
                    'view_type' => $contentType->viewType()->value,
                ],
            ]
        );
    }

    public function update(Request $request): Response
    {
        $identifier = $this->resolveIdentifier($request);

        if ($identifier === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $existing = $this->contentTypes->findByName($identifier);

        if ($existing === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $post = $request->postParams();
        $input = $this->extractInput($post);
        $errors = $this->validateInput($input, false, $existing);

        if ($errors !== []) {
            return $this->renderForm(
                $request,
                'admin/content-types/edit.php',
                [
                    'contentType' => $existing,
                    'errors' => $errors,
                    'old' => $input,
                ],
                422
            );
        }

        $contentType = $this->buildContentType($input);

        if ($contentType === null) {
            return $this->renderForm(
                $request,
                'admin/content-types/edit.php',
                [
                    'contentType' => $existing,
                    'errors' => ['general' => 'Unable to update content type. Please verify the entered values.'],
                    'old' => $input,
                ],
                422
            );
        }

        $this->contentTypes->save($contentType);
        $this->session->flash('content_type_success', sprintf('Content type "%s" updated.', $contentType->label()));

        return Response::redirect('/admin/content-types');
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

    /**
     * @param array<string, mixed> $post
     * @return array{name: string, slug: string, view_type: string}
     */
    private function extractInput(array $post): array
    {
        $name = is_string($post['name'] ?? null) ? trim($post['name']) : '';
        $slug = is_string($post['slug'] ?? null) ? trim($post['slug']) : '';
        $viewType = is_string($post['view_type'] ?? null) ? trim($post['view_type']) : '';

        return [
            'name' => $name,
            'slug' => $slug,
            'view_type' => $viewType,
        ];
    }

    /**
     * @param array{name: string, slug: string, view_type: string} $input
     * @param array<string, string> $errors
     */
    private function renderForm(Request $request, string $template, array $context, int $status = 200): Response
    {
        $html = $this->templateRenderer->renderTemplate(
            $template,
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'templateExistsMap' => $this->templateExistsMap(),
                ...$context,
            ]
        );

        return Response::html($html, $status);
    }

    /**
     * @param array{name: string, slug: string, view_type: string} $input
     * @return array<string, string>
     */
    private function validateInput(array $input, bool $isCreate, ?ContentType $existing = null): array
    {
        $errors = [];

        if ($input['name'] === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($input['slug'] === '') {
            $errors['slug'] = 'Slug is required.';
        } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $input['slug'])) {
            $errors['slug'] = 'Slug must start with a letter and use lowercase letters, numbers, or underscores.';
        }

        if ($input['view_type'] === '' || !in_array($input['view_type'], [ContentViewType::SINGLE->value, ContentViewType::COLLECTION->value], true)) {
            $errors['view_type'] = 'View type must be single or collection.';
        }

        if (!$isCreate && $existing !== null && $input['slug'] !== $existing->name()) {
            $errors['slug'] = 'Slug cannot be changed after creation.';
        }

        if ($isCreate && $input['slug'] !== '' && $this->contentTypes->findByName($input['slug']) !== null) {
            $errors['slug'] = 'A content type with this slug already exists.';
        }

        return $errors;
    }

    /**
     * @param array{name: string, slug: string, view_type: string} $input
     */
    private function buildContentType(array $input): ?ContentType
    {
        try {
            $viewType = ContentViewType::fromString($input['view_type']);

            return new ContentType(
                $input['slug'],
                $input['name'],
                $this->templatePreviewPath($input['slug'], $viewType),
                null,
                $viewType,
            );
        } catch (InvalidContentTypeException | RuntimeException) {
            return null;
        }
    }

    private function resolveIdentifier(Request $request): ?string
    {
        $identifier = $request->attribute('id');

        if (!is_string($identifier) || $identifier === '') {
            return null;
        }

        return $identifier;
    }

    private function templatePreviewPath(string $slug, ContentViewType $viewType): string
    {
        if ($viewType === ContentViewType::COLLECTION) {
            return sprintf('collections/%s.php', $slug);
        }

        return sprintf('content/%s.php', $slug);
    }

    /**
     * @return array<string, bool>
     */
    private function templateExistsMap(): array
    {
        $map = [];

        foreach (['content', 'collections'] as $directory) {
            $pattern = $this->projectRoot . '/templates/' . $directory . '/*.php';
            $files = glob($pattern);

            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                $path = $directory . '/' . basename($file);
                $map[$path] = true;
            }
        }

        return $map;
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
