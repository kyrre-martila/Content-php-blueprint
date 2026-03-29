<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use App\Infrastructure\Editor\EditableFieldRenderer;
use RuntimeException;

final class TemplateRenderer
{
    public function __construct(
        private readonly string $templatesBasePath,
        private readonly ?PatternRenderer $patternRenderer = null,
        private readonly ?EditableFieldRenderer $editableFieldRenderer = null,
        private readonly ?string $siteUrl = null,
        private readonly ?string $siteName = null,
        private readonly ?SeoMetaRenderer $seoMetaRenderer = null,
        private readonly ?StructuredDataRenderer $structuredDataRenderer = null
    ) {
    }

    /**
     * Architectural guardrail: TemplateRenderer coordinates rendering only.
     * Any future cross-cutting rendering concern must be implemented in a
     * dedicated renderer service and delegated from here.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $templatePath, array $data = []): string
    {
        $data = $this->withResolvedMeta($data);
        $layout = null;
        $content = $this->renderFile($templatePath, $data, $layout);

        if ($layout === null) {
            return $this->renderHeadEnhancements($content, $data);
        }

        $layoutPath = $this->resolveLayoutPath($layout);
        $unusedLayout = null;
        $output = $this->renderFile($layoutPath, [...$data, 'content' => $content], $unusedLayout);

        return $this->renderHeadEnhancements($output, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderTemplate(string $templateRelativePath, array $data = []): string
    {
        return $this->render($this->templatesBasePath . '/' . ltrim(trim($templateRelativePath), '/'), $data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function withResolvedMeta(array $data): array
    {
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $contentItem = $data['contentItem'] ?? null;
        $title = $this->firstNonEmptyString(
            $meta['title'] ?? null,
            is_object($contentItem) && method_exists($contentItem, 'metaTitle') ? $contentItem->metaTitle() : null,
            is_object($contentItem) && method_exists($contentItem, 'title') ? $contentItem->title() : null
        );
        $description = $this->firstNonEmptyString(
            $meta['description'] ?? null,
            is_object($contentItem) && method_exists($contentItem, 'metaDescription') ? $contentItem->metaDescription() : null,
            $this->summaryFromContentItem($contentItem)
        );
        $ogImage = $this->firstNonEmptyString(
            $meta['og_image'] ?? null,
            is_object($contentItem) && method_exists($contentItem, 'ogImage') ? $contentItem->ogImage() : null,
            $meta['default_og_image'] ?? null,
            $data['default_og_image'] ?? null
        );
        $twitterCard = $this->firstNonEmptyString($meta['twitter_card'] ?? null) ?? 'summary_large_image';
        $ogType = $this->detectOgType($contentItem);
        $noindex = ($meta['noindex'] ?? null) === true;

        if (!$noindex && is_object($contentItem) && method_exists($contentItem, 'noindex')) {
            $noindex = $contentItem->noindex() === true;
        }

        $existingCanonical = $this->firstNonEmptyString($meta['canonical'] ?? null);

        $canonical = $existingCanonical;

        if ($canonical === null && is_object($contentItem) && method_exists($contentItem, 'canonicalUrl')) {
            $canonicalUrl = $contentItem->canonicalUrl();

            if (is_string($canonicalUrl) && trim($canonicalUrl) !== '') {
                $canonical = trim($canonicalUrl);
            }
        }

        if ($canonical === null && is_object($contentItem) && method_exists($contentItem, 'slug')) {
            $slug = $contentItem->slug();

            if (is_object($slug) && method_exists($slug, 'value')) {
                $canonical = $this->absoluteCanonicalFromPath('/' . ltrim((string) $slug->value(), '/'), $data['request'] ?? null);
            }
        }

        return [
            ...$data,
            'meta' => [
                ...$meta,
                'title' => $title,
                'description' => $description,
                'og_image' => $ogImage,
                'canonical' => $canonical,
                'og_type' => $ogType,
                'twitter_card' => $twitterCard,
                'noindex' => $noindex,
            ],
        ];
    }

    private function renderHeadEnhancements(string $html, array $data): string
    {
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $pageMeta = PageMeta::fromArray($meta);

        $html = $this->resolveSeoMetaRenderer()->renderIntoHead($html, $pageMeta);

        $structuredDataRenderer = $this->resolveStructuredDataRenderer();
        $payload = $structuredDataRenderer->buildPayload($data);

        return $structuredDataRenderer->renderIntoHead($html, $payload);
    }

    private function resolveSeoMetaRenderer(): SeoMetaRenderer
    {
        return $this->seoMetaRenderer ?? new SeoMetaRenderer();
    }

    private function resolveStructuredDataRenderer(): StructuredDataRenderer
    {
        return $this->structuredDataRenderer ?? new StructuredDataRenderer($this->siteUrl, $this->siteName);
    }

    private function absoluteCanonicalFromPath(string $path, mixed $request): string
    {
        $normalizedPath = '/' . ltrim($path, '/');

        if (is_string($this->siteUrl) && trim($this->siteUrl) !== '') {
            return rtrim(trim($this->siteUrl), '/') . $normalizedPath;
        }

        if (is_object($request) && method_exists($request, 'serverParams')) {
            $server = $request->serverParams();
            $host = $server['HTTP_HOST'] ?? null;

            if (is_string($host) && trim($host) !== '') {
                $scheme = $server['REQUEST_SCHEME'] ?? null;

                if (!is_string($scheme) || trim($scheme) === '') {
                    $scheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http';
                }

                return strtolower(trim((string) $scheme)) . '://' . trim($host) . $normalizedPath;
            }
        }

        return $normalizedPath;
    }

    private function detectOgType(mixed $contentItem): string
    {
        if (!is_object($contentItem) || !method_exists($contentItem, 'type')) {
            return 'website';
        }

        $contentType = $contentItem->type();

        if (!is_object($contentType) || !method_exists($contentType, 'name')) {
            return 'website';
        }

        return match (strtolower(trim((string) $contentType->name()))) {
            'article' => 'article',
            'page' => 'website',
            default => 'website',
        };
    }

    private function firstNonEmptyString(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $trimmed = trim($value);

            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    private function summaryFromContentItem(mixed $contentItem): ?string
    {
        if (!is_object($contentItem) || !method_exists($contentItem, 'patternBlocks')) {
            return null;
        }

        $patternBlocks = $contentItem->patternBlocks();

        if (!is_array($patternBlocks) || $patternBlocks === []) {
            return null;
        }

        $summaryParts = [];

        foreach ($patternBlocks as $block) {
            if (!is_array($block) || !is_array($block['data'] ?? null)) {
                continue;
            }

            foreach ($block['data'] as $value) {
                if (!is_string($value)) {
                    continue;
                }

                $normalized = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');

                if ($normalized !== '') {
                    $summaryParts[] = $normalized;
                }
            }
        }

        if ($summaryParts === []) {
            return null;
        }

        return mb_substr(implode(' ', $summaryParts), 0, 160);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderPattern(string $slug, array $data = []): string
    {
        if ($this->patternRenderer === null) {
            return '';
        }

        return $this->patternRenderer->render($slug, $data);
    }

    private function resolveLayoutPath(string $layout): string
    {
        $normalizedLayout = ltrim($layout, '/');
        $fullPath = $this->templatesBasePath . '/' . $normalizedLayout;

        if (!is_file($fullPath)) {
            throw new RuntimeException(sprintf('Layout not found at path "%s".', $normalizedLayout));
        }

        return $fullPath;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $templatePath, array $data, ?string &$layout): string
    {
        if (!is_file($templatePath)) {
            throw new RuntimeException(sprintf('Template not found at path "%s".', $templatePath));
        }

        ob_start();

        $renderTemplate = static function (string $__templatePath, array $__data, ?string &$__layout, self $__renderer): void {
            extract($__data, EXTR_SKIP);

            $renderer = $__renderer;
            $inlineFieldRenderer = $__renderer->editableFieldRenderer ?? new EditableFieldRenderer();
            $e = static fn (string $value): string => htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $isEditorMode = static function () use ($__data): bool {
                return ($__data['editorModeActive'] ?? false) === true && ($__data['editorCanUse'] ?? false) === true;
            };

            $editableText = static function (string $value, array $meta = []) use ($inlineFieldRenderer, $isEditorMode): string {
                return $inlineFieldRenderer->renderText($value, $meta, $isEditorMode());
            };

            $editableTextarea = static function (string $value, array $meta = []) use ($inlineFieldRenderer, $isEditorMode): string {
                return $inlineFieldRenderer->renderTextarea($value, $meta, $isEditorMode());
            };

            include $__templatePath;

            if (isset($layout) && is_string($layout) && trim($layout) !== '') {
                $__layout = $layout;
            }
        };

        $renderTemplate($templatePath, $data, $layout, $this);

        $output = ob_get_clean();

        if ($output === false) {
            throw new RuntimeException('Template output buffering failed.');
        }

        return $output;
    }
}
