<?php

declare(strict_types=1);

namespace App\Application\SEO;

use App\Domain\Content\ContentItem;

final class SitemapGenerator
{
    public function __construct(private readonly string $appUrl)
    {
    }

    /**
     * @param list<ContentItem> $contentItems
     */
    public function generate(array $contentItems): string
    {
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($contentItems as $contentItem) {
            if (!$contentItem->isPublished()) {
                continue;
            }

            $xml[] = '  <url>';
            $xml[] = sprintf('    <loc>%s</loc>', $this->escapeXml($this->resolveAbsoluteUrl($contentItem)));
            $xml[] = sprintf('    <lastmod>%s</lastmod>', $contentItem->updatedAt()->format(DATE_ATOM));
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml) . "\n";
    }

    private function resolveAbsoluteUrl(ContentItem $contentItem): string
    {
        $canonicalUrl = $contentItem->canonicalUrl();

        if (is_string($canonicalUrl) && trim($canonicalUrl) !== '') {
            return trim($canonicalUrl);
        }

        return $this->normalizedBaseUrl() . '/' . ltrim($contentItem->slug()->value(), '/');
    }

    private function normalizedBaseUrl(): string
    {
        return rtrim(trim($this->appUrl), '/');
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
