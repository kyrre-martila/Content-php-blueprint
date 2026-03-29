<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Application\Content\CreateContentItem;
use App\Application\Content\Dto\ContentItemInput;
use App\Application\Content\ListContentItems;
use App\Application\Content\UpdateContentItem;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Pattern\PatternRegistry;
use App\Infrastructure\View\TemplateRenderer;

final class ContentAdminController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly ContentTypeRepositoryInterface $contentTypes,
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly ListContentItems $listContentItems,
        private readonly CreateContentItem $createContentItem,
        private readonly UpdateContentItem $updateContentItem,
        private readonly PatternRegistry $patternRegistry,
        private readonly AuthSession $authSession,
        private readonly SessionManager $session
    ) {
    }

    public function index(Request $request): Response
    {
        $listing = $this->listContentItems->execute();

        $html = $this->templateRenderer->renderTemplate(
            'admin/content/index.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'items' => $listing['items'],
                'pagination' => [
                    'total_count' => $listing['total_count'],
                    'limit' => $listing['limit'],
                    'offset' => $listing['offset'],
                ],
                'success' => $this->session->pullFlash('content_success'),
            ]
        );

        return Response::html($html);
    }

    public function create(Request $request): Response
    {
        $html = $this->templateRenderer->renderTemplate(
            'admin/content/create.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'contentTypes' => $this->contentTypes->findAll(),
                'availablePatterns' => $this->availablePatternsForView(),
                'errors' => [],
                'old' => [
                    'title' => '',
                    'slug' => '',
                    'status' => '',
                    'content_type' => '',
                    'body' => '',
                    'pattern_blocks' => [],
                    'meta_title' => '',
                    'meta_description' => '',
                    'og_image' => '',
                    'canonical_url' => '',
                    'noindex' => false,
                ],
            ]
        );

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        $input = $this->buildInput($request);
        $result = $this->createContentItem->execute($input);

        if (!$result->isValid) {
            return $this->renderCreateWithErrors($request, $result->errors, $input);
        }

        $this->session->flash('content_success', 'Content item created successfully.');

        return Response::redirect('/admin/content');
    }

    public function edit(Request $request): Response
    {
        $id = $this->resolveContentItemId($request);

        if ($id === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $item = $this->contentItems->findById($id);

        if ($item === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $html = $this->templateRenderer->renderTemplate(
            'admin/content/edit.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'contentItem' => $item,
                'contentTypes' => $this->contentTypes->findAll(),
                'availablePatterns' => $this->availablePatternsForView(),
                'errors' => [],
                'old' => [
                    'title' => $item->title(),
                    'slug' => $item->slug()->value(),
                    'status' => $item->status()->value,
                    'content_type' => $item->type()->name(),
                    'body' => '',
                    'pattern_blocks' => $item->patternBlocks(),
                    'meta_title' => $item->metaTitle() ?? '',
                    'meta_description' => $item->metaDescription() ?? '',
                    'og_image' => $item->ogImage() ?? '',
                    'canonical_url' => $item->canonicalUrl() ?? '',
                    'noindex' => $item->noindex(),
                ],
            ]
        );

        return Response::html($html);
    }

    public function update(Request $request): Response
    {
        $id = $this->resolveContentItemId($request);

        if ($id === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $input = $this->buildInput($request);
        $result = $this->updateContentItem->execute($id, $input);

        if (!$result->isValid) {
            return $this->renderEditWithErrors($request, $id, $result->errors, $input);
        }

        $this->session->flash('content_success', 'Content item updated successfully.');

        return Response::redirect('/admin/content');
    }

    public function destroy(Request $request): Response
    {
        // Access control enforced via middleware layer.

        if (!$this->isDeleteMethod($request)) {
            return Response::json(['success' => false], 405);
        }

        if (!$this->hasValidCsrfToken($request)) {
            return Response::json(['success' => false], 419);
        }

        $id = $this->resolveContentItemId($request);

        if ($id === null) {
            return Response::json(['success' => false], 404);
        }

        $contentItem = $this->contentItems->findById($id);

        if ($contentItem === null) {
            return Response::json(['success' => false], 404);
        }

        $this->contentItems->remove($contentItem);

        return Response::json(['success' => true]);
    }

    private function buildInput(Request $request): ContentItemInput
    {
        $post = $request->postParams();

        return new ContentItemInput(
            is_string($post['title'] ?? null) ? $post['title'] : '',
            is_string($post['slug'] ?? null) ? $post['slug'] : '',
            is_string($post['status'] ?? null) ? $post['status'] : '',
            is_string($post['content_type'] ?? null) ? $post['content_type'] : '',
            is_string($post['body'] ?? null) ? $post['body'] : null,
            $this->extractPatternBlocks($post['pattern_blocks'] ?? null),
            is_string($post['meta_title'] ?? null) ? $post['meta_title'] : null,
            is_string($post['meta_description'] ?? null) ? $post['meta_description'] : null,
            is_string($post['og_image'] ?? null) ? $post['og_image'] : null,
            is_string($post['canonical_url'] ?? null) ? $post['canonical_url'] : null,
            $this->toBoolean($post['noindex'] ?? null)
        );
    }

    private function resolveContentItemId(Request $request): ?int
    {
        $id = $request->attribute('id');

        if (!is_string($id) || !ctype_digit($id)) {
            return null;
        }

        return (int) $id;
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderCreateWithErrors(Request $request, array $errors, ContentItemInput $input): Response
    {
        $html = $this->templateRenderer->renderTemplate(
            'admin/content/create.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'contentTypes' => $this->contentTypes->findAll(),
                'availablePatterns' => $this->availablePatternsForView(),
                'errors' => $errors,
                'old' => [
                    'title' => $input->title,
                    'slug' => $input->slug,
                    'status' => $input->status,
                    'content_type' => $input->contentType,
                    'body' => $input->body ?? '',
                    'pattern_blocks' => $input->patternBlocks,
                    'meta_title' => $input->metaTitle ?? '',
                    'meta_description' => $input->metaDescription ?? '',
                    'og_image' => $input->ogImage ?? '',
                    'canonical_url' => $input->canonicalUrl ?? '',
                    'noindex' => $input->noindex,
                ],
            ]
        );

        return Response::html($html, 422);
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderEditWithErrors(Request $request, int $id, array $errors, ContentItemInput $input): Response
    {
        $item = $this->contentItems->findById($id);

        if ($item === null) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $html = $this->templateRenderer->renderTemplate(
            'admin/content/edit.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'contentItem' => $item,
                'contentTypes' => $this->contentTypes->findAll(),
                'availablePatterns' => $this->availablePatternsForView(),
                'errors' => $errors,
                'old' => [
                    'title' => $input->title,
                    'slug' => $input->slug,
                    'status' => $input->status,
                    'content_type' => $input->contentType,
                    'body' => $input->body ?? '',
                    'pattern_blocks' => $input->patternBlocks,
                    'meta_title' => $input->metaTitle ?? '',
                    'meta_description' => $input->metaDescription ?? '',
                    'og_image' => $input->ogImage ?? '',
                    'canonical_url' => $input->canonicalUrl ?? '',
                    'noindex' => $input->noindex,
                ],
            ]
        );

        return Response::html($html, 422);
    }


    /**
     * @return array<string, array{name: string, key: string, description: string, fields: list<array{name: string, type: string}>}>
     */
    private function availablePatternsForView(): array
    {
        $patterns = [];

        foreach ($this->patternRegistry->all() as $key => $metadata) {
            $patterns[$key] = $metadata->toArray();
        }

        return $patterns;
    }

    /**
     * @return list<array{pattern: string, data: array<string, string>}>
     */
    private function extractPatternBlocks(mixed $rawBlocks): array
    {
        if (!is_array($rawBlocks)) {
            return [];
        }

        $availablePatterns = $this->patternRegistry->all();
        $blocks = [];

        foreach ($rawBlocks as $rawBlock) {
            if (!is_array($rawBlock)) {
                continue;
            }

            $slug = $rawBlock['pattern'] ?? null;

            if (!is_string($slug) || !isset($availablePatterns[$slug])) {
                continue;
            }

            $pattern = $availablePatterns[$slug];
            $rawData = $rawBlock['data'] ?? [];

            if (!is_array($rawData)) {
                $rawData = [];
            }

            $data = [];

            foreach ($pattern->fields() as $field) {
                $name = $field['name'];
                $value = $rawData[$name] ?? '';
                $data[$name] = is_scalar($value) ? trim((string) $value) : '';
            }

            $blocks[] = [
                'pattern' => $slug,
                'data' => $data,
            ];
        }

        return $blocks;
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (!is_scalar($value)) {
            return false;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function isDeleteMethod(Request $request): bool
    {
        if ($request->method() === 'DELETE') {
            return true;
        }

        if ($request->method() !== 'POST') {
            return false;
        }

        $methodOverride = $request->postParams()['_method'] ?? null;

        return is_string($methodOverride) && strtoupper(trim($methodOverride)) === 'DELETE';
    }

    private function hasValidCsrfToken(Request $request): bool
    {
        $sessionToken = $this->session->get('_csrf_token');

        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        $submittedToken = $request->postParams()['_csrf_token'] ?? null;

        if (!is_string($submittedToken) || $submittedToken === '') {
            $server = $request->serverParams();
            $headerToken = $server['HTTP_X_CSRF_TOKEN'] ?? null;
            $submittedToken = is_string($headerToken) ? $headerToken : null;
        }

        return is_string($submittedToken) && hash_equals($sessionToken, $submittedToken);
    }
}
