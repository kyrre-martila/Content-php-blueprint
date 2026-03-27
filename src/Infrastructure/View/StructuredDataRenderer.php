<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use DateTimeInterface;

final class StructuredDataRenderer
{
    public function __construct(
        private readonly ?string $siteUrl = null,
        private readonly ?string $siteName = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function buildPayload(array $data): StructuredDataPayload
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

        return new StructuredDataPayload($graph);
    }

    public function renderIntoHead(string $html, StructuredDataPayload $payload): string
    {
        if (!preg_match('/<\/head>/i', $html)) {
            return $html;
        }

        $html = $this->stripExistingStructuredData($html);

        if ($payload->isEmpty()) {
            return $html;
        }

        $json = json_encode($payload->toJsonLdDocument(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!is_string($json) || $json === '') {
            return $html;
        }

        $block = sprintf(
            "\n    <script type=\"application/ld+json\" data-schema-source=\"template-renderer\">%s</script>\n",
            $json
        );

        return (string) preg_replace('/<\/head>/i', $block . '</head>', $html, 1);
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
        $datePublished = is_object($contentItem) && method_exists($contentItem, 'createdAt') && $contentItem->createdAt() instanceof DateTimeInterface
            ? $contentItem->createdAt()->format(DateTimeInterface::ATOM)
            : null;
        $dateModified = is_object($contentItem) && method_exists($contentItem, 'updatedAt') && $contentItem->updatedAt() instanceof DateTimeInterface
            ? $contentItem->updatedAt()->format(DateTimeInterface::ATOM)
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

    private function stripExistingStructuredData(string $html): string
    {
        $pattern = '/^\s*<script\b[^>]*type\s*=\s*(["\'])application\/ld\+json\1[^>]*data-schema-source\s*=\s*(["\'])template-renderer\2[^>]*>.*?<\/script>\s*$/ims';

        return (string) preg_replace($pattern, '', $html);
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
}
