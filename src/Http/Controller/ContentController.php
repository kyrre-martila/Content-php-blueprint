<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Content\Exception\InvalidSlugException;
use App\Domain\Content\Repository\CategoryGroupRepositoryInterface;
use App\Domain\Content\Repository\CategoryRepositoryInterface;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Slug;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class ContentController
{
    public function __construct(
        private readonly CategoryGroupRepositoryInterface $categoryGroups,
        private readonly CategoryRepositoryInterface $categories,
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly TemplateResolver $templateResolver,
        private readonly TemplateRenderer $templateRenderer,
        private readonly EditorMode $editorMode
    ) {
    }

    public function show(Request $request): Response
    {
        $slugInput = $request->attribute('slug');

        if (!is_string($slugInput) || trim($slugInput) === '') {
            return $this->renderNotFound($request);
        }

        try {
            $slug = Slug::fromString($slugInput);
        } catch (InvalidSlugException) {
            return $this->renderNotFound($request);
        }

        $contentItem = $this->contentItems->findBySlug($slug);

        if ($contentItem === null || !$contentItem->isPublished()) {
            return $this->renderNotFound($request);
        }

        $canonicalRedirect = $this->resolveCanonicalRedirect($request, $contentItem->slug()->value(), $contentItem->canonicalUrl());

        if ($canonicalRedirect !== null) {
            return Response::redirect($canonicalRedirect, 301);
        }

        $templatePath = $contentItem->type()->isCollectionView()
            ? $this->templateResolver->resolveCollectionTemplate($contentItem->type())
            : $this->templateResolver->resolveContentTemplate($contentItem->type());
        $viewData = [
            'contentItem' => $contentItem,
            'request' => $request,
            'slug' => $slug->value(),
            'patternBlocks' => $contentItem->patternBlocks(),
            'meta' => [
                'noindex' => $contentItem->noindex(),
            ],
            'editorModeActive' => $this->editorMode->isActive(),
            'editorCanUse' => $this->editorMode->canUse(),
        ];

        if ($contentItem->type()->isCollectionView()) {
            $page = $this->positiveIntQueryParam($request, 'page', 1);
            $perPage = $this->positiveIntQueryParam($request, 'perPage', ContentItemRepositoryInterface::DEFAULT_LIMIT);
            $offset = ($page - 1) * $perPage;

            $result = $this->contentItems->findPublishedByType($contentItem->type(), $perPage, $offset);
            $viewData = array_merge($viewData, $this->buildCollectionViewModel(
                $result,
                $page,
                $perPage,
                ['contentItem' => $contentItem]
            ));
        }

        $html = $this->templateRenderer->render($templatePath, $viewData);

        return Response::html($html);
    }

    public function showCategoryCollection(Request $request): Response
    {
        $groupSlug = $request->attribute('groupSlug');
        $categorySlug = $request->attribute('categorySlug');

        if (!is_string($groupSlug) || trim($groupSlug) === '' || !is_string($categorySlug) || trim($categorySlug) === '') {
            return $this->renderNotFound($request);
        }

        $categoryGroup = $this->categoryGroups->findBySlug($groupSlug);

        if ($categoryGroup === null) {
            return $this->renderNotFound($request);
        }

        $category = $this->categories->findBySlugInGroup($categoryGroup, $categorySlug);

        if ($category === null) {
            return $this->renderNotFound($request);
        }

        $page = $this->positiveIntQueryParam($request, 'page', 1);
        $perPage = $this->positiveIntQueryParam($request, 'perPage', ContentItemRepositoryInterface::DEFAULT_LIMIT);
        $offset = ($page - 1) * $perPage;
        $result = $this->contentItems->findPublishedByCategory($category, $perPage, $offset);

        $templatePath = $this->templateResolver->resolveCategoryCollectionTemplate($categoryGroup);

        $viewData = $this->buildCollectionViewModel($result, $page, $perPage, [
            'request' => $request,
            'contentItem' => null,
            'categoryGroup' => $categoryGroup,
            'category' => $category,
            'breadcrumbs' => [
                ['label' => 'Categories', 'url' => '/categories'],
                ['label' => $categoryGroup->name(), 'url' => '/categories/' . $categoryGroup->slug()->value()],
                ['label' => $category->name(), 'url' => '/categories/' . $categoryGroup->slug()->value() . '/' . $category->slug()->value()],
            ],
            'editorModeActive' => $this->editorMode->isActive(),
            'editorCanUse' => $this->editorMode->canUse(),
        ]);
        $html = $this->templateRenderer->render($templatePath, $viewData);

        return Response::html($html);
    }

    private function resolveCanonicalRedirect(Request $request, string $slug, ?string $canonicalUrl): ?string
    {
        $canonicalTarget = $this->canonicalTarget($slug, $canonicalUrl);
        $requestedNormalizedPath = $this->normalizeComparablePath($request->rawPath());
        $canonicalPath = $this->extractPathFromTarget($canonicalTarget);
        $canonicalComparablePath = $this->normalizeComparablePath($canonicalPath);

        if ($requestedNormalizedPath !== $canonicalComparablePath) {
            return $this->targetWithQuery($canonicalTarget, $request->queryParams());
        }

        if ($canonicalUrl !== null && trim($canonicalUrl) !== '' && !$this->isAlreadyCanonicalAbsoluteUrl($request, $canonicalUrl)) {
            return $this->targetWithQuery($canonicalTarget, $request->queryParams());
        }

        if ($request->rawPath() !== $canonicalPath) {
            return $this->targetWithQuery($canonicalTarget, $request->queryParams());
        }

        return null;
    }

    private function canonicalTarget(string $slug, ?string $canonicalUrl): string
    {
        if (is_string($canonicalUrl) && trim($canonicalUrl) !== '') {
            return trim($canonicalUrl);
        }

        return '/' . ltrim($slug, '/');
    }

    private function extractPathFromTarget(string $target): string
    {
        $path = parse_url($target, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return '/';
        }

        return $path;
    }

    private function normalizeComparablePath(string $path): string
    {
        $normalized = strtolower(trim($path));

        if ($normalized === '') {
            return '/';
        }

        if (!str_starts_with($normalized, '/')) {
            $normalized = '/' . $normalized;
        }

        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;
        $normalized = preg_replace('#/index$#', '', $normalized) ?? $normalized;

        if (strlen($normalized) > 1) {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized === '' ? '/' : $normalized;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function targetWithQuery(string $target, array $query): string
    {
        if ($query === []) {
            return $target;
        }

        $queryString = http_build_query($query);

        if ($queryString === '') {
            return $target;
        }

        $separator = str_contains($target, '?') ? '&' : '?';

        return $target . $separator . $queryString;
    }

    private function isAlreadyCanonicalAbsoluteUrl(Request $request, string $canonicalUrl): bool
    {
        $canonicalParts = parse_url($canonicalUrl);

        if (!is_array($canonicalParts)) {
            return false;
        }

        if (!isset($canonicalParts['scheme'], $canonicalParts['host'])) {
            return true;
        }

        $requestHost = $request->serverParams()['HTTP_HOST'] ?? null;
        $requestScheme = $request->serverParams()['REQUEST_SCHEME'] ?? null;

        if (!is_string($requestHost) || trim($requestHost) === '') {
            return false;
        }

        if (!is_string($requestScheme) || trim($requestScheme) === '') {
            $requestScheme = (!empty($request->serverParams()['HTTPS']) && $request->serverParams()['HTTPS'] !== 'off') ? 'https' : 'http';
        }

        $canonicalScheme = strtolower((string) $canonicalParts['scheme']);
        $canonicalHost = strtolower((string) $canonicalParts['host']);
        $currentHost = strtolower(trim($requestHost));
        $currentScheme = strtolower(trim($requestScheme));
        $canonicalPath = $this->normalizeComparablePath((string) ($canonicalParts['path'] ?? '/'));
        $requestPath = $this->normalizeComparablePath($request->rawPath());

        return $canonicalScheme === $currentScheme
            && $canonicalHost === $currentHost
            && $canonicalPath === $requestPath;
    }

    private function positiveIntQueryParam(Request $request, string $key, int $fallback): int
    {
        $queryParams = $request->queryParams();
        $value = $queryParams[$key] ?? null;

        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_INT);

            if (is_int($parsed) && $parsed > 0) {
                return $parsed;
            }
        }

        return $fallback;
    }

    /**
     * @param array{
     *   items?: list<mixed>,
     *   total_count?: int,
     *   offset?: int
     * } $result
     * @param array<string, mixed> $extra
     * @return array{
     *   contentItem: mixed,
     *   collectionItems: list<mixed>,
     *   pagination: array{
     *      totalCount: int,
     *      currentPage: int,
     *      perPage: int,
     *      offset: int,
     *      totalPages: int
     *   },
     *   totalCount: int,
     *   currentPage: int,
     *   perPage: int
     * }&array<string, mixed>
     */
    private function buildCollectionViewModel(array $result, int $page, int $perPage, array $extra = []): array
    {
        $items = $result['items'] ?? [];
        $totalCount = $result['total_count'] ?? 0;
        $offset = $result['offset'] ?? (($page - 1) * $perPage);

        return array_merge([
            'contentItem' => null,
            'collectionItems' => is_array($items) ? array_values($items) : [],
            'pagination' => [
                'totalCount' => is_int($totalCount) ? $totalCount : 0,
                'currentPage' => $page,
                'perPage' => $perPage,
                'offset' => is_int($offset) ? max(0, $offset) : 0,
                'totalPages' => $perPage > 0 && is_int($totalCount) ? (int) ceil($totalCount / $perPage) : 0,
            ],
            'totalCount' => is_int($totalCount) ? $totalCount : 0,
            'currentPage' => $page,
            'perPage' => $perPage,
        ], $extra);
    }

    private function renderNotFound(Request $request): Response
    {
        $html = $this->templateRenderer->render($this->templateResolver->resolveNotFound(), [
            'request' => $request,
            'editorModeActive' => $this->editorMode->isActive(),
            'editorCanUse' => $this->editorMode->canUse(),
        ]);

        return Response::html($html, 404);
    }

}
