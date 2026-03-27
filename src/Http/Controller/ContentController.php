<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Content\Exception\InvalidSlugException;
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

        $templatePath = $this->templateResolver->resolveContentTemplate();
        $contentSummary = $this->buildSummaryFromPatternBlocks($contentItem->patternBlocks());
        $metaTitle = $contentItem->metaTitle();
        $metaDescription = $contentItem->metaDescription() ?? ($contentSummary !== '' ? $contentSummary : null);

        $html = $this->templateRenderer->render($templatePath, [
            'contentItem' => $contentItem,
            'request' => $request,
            'slug' => $slug->value(),
            'patternBlocks' => $contentItem->patternBlocks(),
            'meta' => [
                'title' => $metaTitle ?? $contentItem->title(),
                'description' => $metaDescription,
                'og_image' => $contentItem->ogImage(),
                'canonical' => $contentItem->canonicalUrl(),
                'noindex' => $contentItem->noindex(),
            ],
            'editorModeActive' => $this->editorMode->isActive(),
            'editorCanUse' => $this->editorMode->canUse(),
        ]);

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

    private function renderNotFound(Request $request): Response
    {
        $html = $this->templateRenderer->render($this->templateResolver->resolveNotFound(), [
            'request' => $request,
            'editorModeActive' => $this->editorMode->isActive(),
            'editorCanUse' => $this->editorMode->canUse(),
        ]);

        return Response::html($html, 404);
    }

    /**
     * @param list<array{pattern: string, data: array<string, string>}> $patternBlocks
     */
    private function buildSummaryFromPatternBlocks(array $patternBlocks): string
    {
        $parts = [];

        foreach ($patternBlocks as $block) {
            foreach ($block['data'] as $value) {
                if (!is_string($value)) {
                    continue;
                }

                $trimmed = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');

                if ($trimmed === '') {
                    continue;
                }

                $parts[] = $trimmed;
            }
        }

        if ($parts === []) {
            return '';
        }

        $summary = implode(' ', $parts);

        return mb_substr($summary, 0, 160);
    }
}
