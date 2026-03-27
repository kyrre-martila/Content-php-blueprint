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
        private readonly ?string $siteName = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $templatePath, array $data = []): string
    {
        $data = $this->withResolvedMeta($data);
        $layout = null;
        $content = $this->renderFile($templatePath, $data, $layout);

        if ($layout === null) {
            $output = $this->injectSocialMetadata($content, $data);

            return $this->injectStructuredData($output, $data);
        }

        $layoutPath = $this->resolveLayoutPath($layout);
        $unusedLayout = null;

        $output = $this->renderFile($layoutPath, [...$data, 'content' => $content], $unusedLayout);
        $output = $this->injectSocialMetadata($output, $data);

        return $this->injectStructuredData($output, $data);
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

    /**
     * @param array<string, mixed> $data
     */
    private function injectSocialMetadata(string $html, array $data): string
    {
        if (!preg_match('/<\/head>/i', $html)) {
            return $html;
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $title = $this->firstNonEmptyString($meta['title'] ?? null);
        $description = $this->firstNonEmptyString($meta['description'] ?? null);
        $ogImage = $this->firstNonEmptyString($meta['og_image'] ?? null);
        $canonical = $this->firstNonEmptyString($meta['canonical'] ?? null);
        $ogType = $this->firstNonEmptyString($meta['og_type'] ?? null) ?? 'website';
        $twitterCard = $this->firstNonEmptyString($meta['twitter_card'] ?? null) ?? 'summary_large_image';

        $html = $this->stripExistingSocialMetadata($html);

        $tags = [];
        $tags[] = $this->metaPropertyTag('og:type', $ogType);
        $tags[] = $this->metaNameTag('twitter:card', $twitterCard);

        if ($title !== null) {
            $tags[] = $this->metaPropertyTag('og:title', $title);
            $tags[] = $this->metaNameTag('twitter:title', $title);
        }

        if ($description !== null) {
            $tags[] = $this->metaPropertyTag('og:description', $description);
            $tags[] = $this->metaNameTag('twitter:description', $description);
        }

        if ($canonical !== null) {
            $tags[] = $this->metaPropertyTag('og:url', $canonical);
        }

        if ($ogImage !== null) {
            $tags[] = $this->metaPropertyTag('og:image', $ogImage);
            $tags[] = $this->metaNameTag('twitter:image', $ogImage);
        }

        $metadataBlock = "\n    " . implode("\n    ", $tags) . "\n";

        return (string) preg_replace('/<\/head>/i', $metadataBlock . '</head>', $html, 1);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function injectStructuredData(string $html, array $data): string
    {
        if (!preg_match('/<\/head>/i', $html)) {
            return $html;
        }

        $html = $this->stripExistingStructuredData($html);
        $graph = $this->buildStructuredDataGraph($data);

        if ($graph === []) {
            return $html;
        }

        $json = json_encode(
            [
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if (!is_string($json) || $json === '') {
            return $html;
        }

        $block = sprintf(
            "\n    <script type=\"application/ld+json\" data-schema-source=\"template-renderer\">%s</script>\n",
            $json
        );

        return (string) preg_replace('/<\/head>/i', $block . '</head>', $html, 1);
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

    private function stripExistingSocialMetadata(string $html): string
    {
        $socialTagPattern = '/^\s*<meta\b[^>]*(?:property|name)\s*=\s*(["\'])(?:og:(?:title|description|image|url|type)|twitter:(?:card|title|description|image))\1[^>]*>\s*$/im';

        return (string) preg_replace($socialTagPattern, '', $html);
    }

    private function stripExistingStructuredData(string $html): string
    {
        $pattern = '/^\s*<script\b[^>]*type\s*=\s*(["\'])application\/ld\+json\1[^>]*data-schema-source\s*=\s*(["\'])template-renderer\2[^>]*>.*?<\/script>\s*$/ims';

        return (string) preg_replace($pattern, '', $html);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    private function buildStructuredDataGraph(array $data): array
    {
        $graph = [];
        $webSiteSchema = $this->buildWebSiteSchema();
        $organizationSchema = $this->buildOrganizationSchema();
        $webPageSchema = $this->buildWebPageSchema($data);
        $articleSchema = $this->buildArticleSchema($data);
        $breadcrumbSchema = $this->buildBreadcrumbListSchema($data);

        if ($webSiteSchema !== null) {
            $graph[] = $webSiteSchema;
        }

        if ($organizationSchema !== null) {
            $graph[] = $organizationSchema;
        }

        if ($webPageSchema !== null) {
            $graph[] = $webPageSchema;
        }

        if ($articleSchema !== null) {
            $graph[] = $articleSchema;
        }

        if ($breadcrumbSchema !== null) {
            $graph[] = $breadcrumbSchema;
        }

        return $graph;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildWebSiteSchema(): ?array
    {
        $name = $this->resolvedSiteName();
        $url = $this->resolvedSiteUrl();

        if ($name === null && $url === null) {
            return null;
        }

        $schema = ['@type' => 'WebSite'];

        if ($name !== null) {
            $schema['name'] = $name;
        }

        if ($url !== null) {
            $schema['url'] = $url;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildOrganizationSchema(): ?array
    {
        $name = $this->resolvedSiteName();
        $url = $this->resolvedSiteUrl();

        if ($name === null && $url === null) {
            return null;
        }

        $schema = ['@type' => 'Organization'];

        if ($name !== null) {
            $schema['name'] = $name;
        }

        if ($url !== null) {
            $schema['url'] = $url;
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function buildWebPageSchema(array $data): ?array
    {
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $contentItem = $data['contentItem'] ?? null;
        $name = $this->firstNonEmptyString(
            $meta['title'] ?? null,
            is_object($contentItem) && method_exists($contentItem, 'title') ? $contentItem->title() : null
        );
        $url = $this->firstNonEmptyString($meta['canonical'] ?? null);
        $description = $this->firstNonEmptyString(
            $meta['description'] ?? null,
            $this->summaryFromContentItem($contentItem)
        );

        if ($name === null && $url === null && $description === null) {
            return null;
        }

        $schema = ['@type' => 'WebPage'];

        if ($name !== null) {
            $schema['name'] = $name;
        }

        if ($url !== null) {
            $schema['url'] = $url;
        }

        if ($description !== null) {
            $schema['description'] = $description;
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function buildArticleSchema(array $data): ?array
    {
        $contentItem = $data['contentItem'] ?? null;

        if (!$this->isArticleContentType($contentItem)) {
            return null;
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $headline = $this->firstNonEmptyString(
            $meta['title'] ?? null,
            is_object($contentItem) && method_exists($contentItem, 'title') ? $contentItem->title() : null
        );
        $datePublished = is_object($contentItem) && method_exists($contentItem, 'createdAt')
            ? $contentItem->createdAt()->format(\DateTimeInterface::ATOM)
            : null;
        $dateModified = is_object($contentItem) && method_exists($contentItem, 'updatedAt')
            ? $contentItem->updatedAt()->format(\DateTimeInterface::ATOM)
            : null;
        $mainEntityOfPage = $this->firstNonEmptyString($meta['canonical'] ?? null);
        $authorName = $this->resolvedAuthorName($data, $contentItem);

        $schema = ['@type' => 'Article'];

        if ($headline !== null) {
            $schema['headline'] = $headline;
        }

        if ($datePublished !== null) {
            $schema['datePublished'] = $datePublished;
        }

        if ($dateModified !== null) {
            $schema['dateModified'] = $dateModified;
        }

        if ($mainEntityOfPage !== null) {
            $schema['mainEntityOfPage'] = $mainEntityOfPage;
        }

        if ($authorName !== null) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $authorName,
            ];
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function buildBreadcrumbListSchema(array $data): ?array
    {
        $breadcrumbs = $this->extractBreadcrumbs($data);

        if ($breadcrumbs === []) {
            return null;
        }

        $items = [];

        foreach ($breadcrumbs as $breadcrumb) {
            $name = $this->firstNonEmptyString($breadcrumb['name'] ?? null);
            $item = $this->firstNonEmptyString($breadcrumb['item'] ?? null);

            if ($name === null || $item === null) {
                continue;
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $name,
                'item' => $item,
            ];
        }

        if ($items === []) {
            return null;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private function resolvedSiteName(): ?string
    {
        return $this->firstNonEmptyString($this->siteName, 'Content PHP Blueprint');
    }

    private function resolvedSiteUrl(): ?string
    {
        return $this->firstNonEmptyString($this->siteUrl);
    }

    private function isArticleContentType(mixed $contentItem): bool
    {
        if (!is_object($contentItem) || !method_exists($contentItem, 'type')) {
            return false;
        }

        $contentType = $contentItem->type();

        if (!is_object($contentType) || !method_exists($contentType, 'name')) {
            return false;
        }

        return strtolower(trim((string) $contentType->name())) === 'article';
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array{name?: mixed, item?: mixed}>
     */
    private function extractBreadcrumbs(array $data): array
    {
        $breadcrumbs = $data['breadcrumbs'] ?? null;

        if (is_array($breadcrumbs)) {
            return $breadcrumbs;
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        if (is_array($meta['breadcrumbs'] ?? null)) {
            /** @var list<array{name?: mixed, item?: mixed}> $breadcrumbList */
            $breadcrumbList = $meta['breadcrumbs'];

            return $breadcrumbList;
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolvedAuthorName(array $data, mixed $contentItem): ?string
    {
        if (is_string($data['author'] ?? null)) {
            return $this->firstNonEmptyString($data['author']);
        }

        if (is_array($data['author'] ?? null)) {
            return $this->firstNonEmptyString($data['author']['name'] ?? null);
        }

        if (is_object($contentItem) && method_exists($contentItem, 'author')) {
            $author = $contentItem->author();

            if (is_string($author)) {
                return $this->firstNonEmptyString($author);
            }

            if (is_object($author) && method_exists($author, 'name')) {
                return $this->firstNonEmptyString((string) $author->name());
            }
        }

        return null;
    }

    private function metaPropertyTag(string $property, string $content): string
    {
        return sprintf(
            '<meta property="%s" content="%s">',
            htmlspecialchars($property, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    private function metaNameTag(string $name, string $content): string
    {
        return sprintf(
            '<meta name="%s" content="%s">',
            htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
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
