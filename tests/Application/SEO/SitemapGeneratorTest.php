<?php

declare(strict_types=1);

use App\Application\SEO\SitemapGenerator;
use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\Slug;

it('builds a valid sitemap with canonical_url fallback and absolute APP_URL links', function (): void {
    $generator = new SitemapGenerator('https://contentphp.martila.no');

    $publishedWithCanonical = buildSitemapContentItem(
        slug: 'about',
        status: ContentStatus::Published,
        updatedAt: '2026-03-27T12:00:00+00:00',
        canonicalUrl: 'https://example.com/custom-about'
    );

    $publishedWithSlugFallback = buildSitemapContentItem(
        slug: 'contact',
        status: ContentStatus::Published,
        updatedAt: '2026-03-27T13:00:00+00:00'
    );

    $draft = buildSitemapContentItem(
        slug: 'draft-page',
        status: ContentStatus::Draft,
        updatedAt: '2026-03-27T14:00:00+00:00'
    );

    $xml = $generator->generate([$publishedWithCanonical, $publishedWithSlugFallback, $draft]);

    expect($xml)
        ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        ->toContain('<loc>https://example.com/custom-about</loc>')
        ->toContain('<loc>https://contentphp.martila.no/contact</loc>')
        ->toContain('<lastmod>2026-03-27T12:00:00+00:00</lastmod>')
        ->toContain('<lastmod>2026-03-27T13:00:00+00:00</lastmod>')
        ->not->toContain('draft-page');
});

function buildSitemapContentItem(
    string $slug,
    ContentStatus $status,
    string $updatedAt,
    ?string $canonicalUrl = null
): ContentItem {
    return new ContentItem(
        id: random_int(1, 1000),
        type: new ContentType('page', 'Page', 'content/default.php'),
        title: ucfirst($slug),
        slug: Slug::fromString($slug),
        status: $status,
        createdAt: new DateTimeImmutable('2026-03-27T10:00:00+00:00'),
        updatedAt: new DateTimeImmutable($updatedAt),
        canonicalUrl: $canonicalUrl
    );
}
