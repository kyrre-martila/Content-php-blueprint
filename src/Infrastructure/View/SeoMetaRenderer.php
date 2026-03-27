<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

final class SeoMetaRenderer
{
    public function renderIntoHead(string $html, PageMeta $pageMeta): string
    {
        if (!preg_match('/<\/head>/i', $html)) {
            return $html;
        }

        $html = $this->stripExistingManagedTags($html);

        $tags = [];

        if ($pageMeta->canonical !== null) {
            $tags[] = $this->canonicalTag($pageMeta->canonical);
            $tags[] = $this->metaPropertyTag('og:url', $pageMeta->canonical);
        }

        $tags[] = $this->metaPropertyTag('og:type', $pageMeta->ogType);
        $tags[] = $this->metaNameTag('twitter:card', $pageMeta->twitterCard);

        if ($pageMeta->title !== null) {
            $tags[] = $this->metaPropertyTag('og:title', $pageMeta->title);
            $tags[] = $this->metaNameTag('twitter:title', $pageMeta->title);
        }

        if ($pageMeta->description !== null) {
            $tags[] = $this->metaNameTag('description', $pageMeta->description);
            $tags[] = $this->metaPropertyTag('og:description', $pageMeta->description);
            $tags[] = $this->metaNameTag('twitter:description', $pageMeta->description);
        }

        if ($pageMeta->ogImage !== null) {
            $tags[] = $this->metaPropertyTag('og:image', $pageMeta->ogImage);
            $tags[] = $this->metaNameTag('twitter:image', $pageMeta->ogImage);
        }

        $metadataBlock = "\n    " . implode("\n    ", $tags) . "\n";

        return (string) preg_replace('/<\/head>/i', $metadataBlock . '</head>', $html, 1);
    }

    private function stripExistingManagedTags(string $html): string
    {
        $patterns = [
            '/^\s*<link\b[^>]*rel\s*=\s*(["\'])canonical\1[^>]*>\s*$/im',
            '/^\s*<meta\b[^>]*(?:property|name)\s*=\s*(["\'])(?:og:(?:title|description|image|url|type)|twitter:(?:card|title|description|image)|description)\1[^>]*>\s*$/im',
        ];

        foreach ($patterns as $pattern) {
            $html = (string) preg_replace($pattern, '', $html);
        }

        return $html;
    }

    private function canonicalTag(string $href): string
    {
        return sprintf(
            '<link rel="canonical" href="%s">',
            htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
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
}
