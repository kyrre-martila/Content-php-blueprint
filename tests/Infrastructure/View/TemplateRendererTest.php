<?php

declare(strict_types=1);

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\Slug;
use App\Http\Request;
use App\Infrastructure\View\TemplateRenderer;

it('auto-injects canonical link from slug when canonical metadata is absent', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $renderer = new TemplateRenderer($templatesBasePath, null, null, 'https://example.com');

    $html = $renderer->render($templatesBasePath . '/index.php', [
        'contentItem' => makeRenderableContentItem('about'),
        'request' => new Request('GET', '/about', [], [], [], [], ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https']),
        'patternBlocks' => [],
        'meta' => ['title' => 'About'],
    ]);

    expect($html)->toContain('<link rel="canonical" href="https://example.com/about">');
});

it('prefers canonical_url metadata value for canonical link output', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $renderer = new TemplateRenderer($templatesBasePath, null, null, 'https://example.com');

    $html = $renderer->render($templatesBasePath . '/index.php', [
        'contentItem' => makeRenderableContentItem('about', 'https://cdn.example.com/about'),
        'request' => new Request('GET', '/about', [], [], [], [], ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https']),
        'patternBlocks' => [],
        'meta' => ['title' => 'About'],
    ]);

    expect($html)->toContain('<link rel="canonical" href="https://cdn.example.com/about">');
});

function makeRenderableContentItem(string $slug, ?string $canonicalUrl = null): ContentItem
{
    $now = new \DateTimeImmutable('2026-03-27 00:00:00');

    return new ContentItem(
        id: 1,
        type: new ContentType('page', 'Page', 'content/default.php'),
        title: 'About',
        slug: Slug::fromString($slug),
        status: ContentStatus::Published,
        createdAt: $now,
        updatedAt: $now,
        patternBlocks: [],
        canonicalUrl: $canonicalUrl
    );
}
