<?php

declare(strict_types=1);

namespace App\Application\SEO;

final class RobotsGenerator
{
    public function __construct(
        private readonly string $environment,
        private readonly string $appUrl
    ) {
    }

    public function generate(): string
    {
        if (!$this->isProductionEnvironment()) {
            return implode("\n", [
                'User-agent: *',
                'Disallow: /',
                '',
            ]);
        }

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            // Keep all privileged/operator surfaces non-indexable even in production.
            'Disallow: /admin',
            'Disallow: /editor',
            'Disallow: /editor-mode',
            'Disallow: /dev',
            'Disallow: /install',
            'Sitemap: ' . $this->buildSitemapUrl(),
            '',
        ]);
    }

    private function isProductionEnvironment(): bool
    {
        return strtolower(trim($this->environment)) === 'production';
    }

    private function buildSitemapUrl(): string
    {
        return rtrim(trim($this->appUrl), '/') . '/sitemap.xml';
    }
}
