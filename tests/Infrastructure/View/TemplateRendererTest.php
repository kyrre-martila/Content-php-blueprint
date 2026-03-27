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
    $renderer = new TemplateRenderer($templatesBasePath, null, null, 'https://example.com', 'Example Site');

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
    $renderer = new TemplateRenderer($templatesBasePath, null, null, 'https://example.com', 'Example Site');

    $html = $renderer->render($templatesBasePath . '/index.php', [
        'contentItem' => makeRenderableContentItem('about', 'https://cdn.example.com/about'),
        'request' => new Request('GET', '/about', [], [], [], [], ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https']),
        'patternBlocks' => [],
        'meta' => ['title' => 'About'],
    ]);

    expect($html)->toContain('<link rel="canonical" href="https://cdn.example.com/about">');
});

it('auto-injects opengraph and twitter metadata from content metadata with fallbacks', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $renderer = new TemplateRenderer($templatesBasePath, null, null, 'https://example.com', 'Example Site');

    $html = $renderer->render($templatesBasePath . '/index.php', [
        'contentItem' => makeRenderableContentItem(
            slug: 'about',
            canonicalUrl: null,
            typeName: 'article',
            metaTitle: null,
            metaDescription: null,
            ogImage: null,
            patternBlocks: [[
                'pattern' => 'text-block',
                'data' => [
                    'body' => 'This summary is automatically generated from pattern blocks for social metadata fallback handling.',
                ],
            ]]
        ),
        'request' => new Request('GET', '/about', [], [], [], [], ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https']),
        'patternBlocks' => [],
        'meta' => ['default_og_image' => 'https://example.com/default-og.png'],
    ]);

    expect($html)->toContain('<meta property="og:type" content="article">');
    expect($html)->toContain('<meta property="og:title" content="About">');
    expect($html)->toContain('<meta property="og:description" content="This summary is automatically generated from pattern blocks for social metadata fallback handling.">');
    expect($html)->toContain('<meta property="og:url" content="https://example.com/about">');
    expect($html)->toContain('<meta property="og:image" content="https://example.com/default-og.png">');
    expect($html)->toContain('<meta name="twitter:card" content="summary_large_image">');
    expect($html)->toContain('<meta name="twitter:title" content="About">');
    expect($html)->toContain('<meta name="twitter:description" content="This summary is automatically generated from pattern blocks for social metadata fallback handling.">');
    expect($html)->toContain('<meta name="twitter:image" content="https://example.com/default-og.png">');
});

it('injects social metadata only once by replacing manual tags', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $renderer = new TemplateRenderer($templatesBasePath, null, null, 'https://example.com', 'Example Site');

    $html = $renderer->render($templatesBasePath . '/index.php', [
        'contentItem' => makeRenderableContentItem(
            slug: 'about',
            canonicalUrl: 'https://example.com/about',
            typeName: 'page',
            metaTitle: 'Managed title',
            metaDescription: 'Managed description',
            ogImage: 'https://example.com/managed.png'
        ),
        'request' => new Request('GET', '/about', [], [], [], [], ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https']),
        'patternBlocks' => [],
        'meta' => [
            'title' => 'Managed title',
            'description' => 'Managed description',
            'og_image' => 'https://example.com/managed.png',
            'canonical' => 'https://example.com/about',
            'twitter_card' => 'summary_large_image',
        ],
    ]);

    expect(substr_count($html, 'property="og:title"'))->toBe(1);
    expect(substr_count($html, 'name="twitter:title"'))->toBe(1);
    expect(substr_count($html, 'property="og:image"'))->toBe(1);
    expect(substr_count($html, 'name="twitter:image"'))->toBe(1);
    expect(substr_count($html, 'property="og:url"'))->toBe(1);
    expect(substr_count($html, 'property="og:type"'))->toBe(1);
});

it('injects JSON-LD graph with global and page schema data', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $renderer = new TemplateRenderer($templatesBasePath, null, null, 'https://example.com', 'Example Site');

    $html = $renderer->render($templatesBasePath . '/index.php', [
        'contentItem' => makeRenderableContentItem(
            slug: 'about',
            canonicalUrl: null,
            typeName: 'page',
            metaTitle: 'About metadata title',
            metaDescription: 'About metadata description'
        ),
        'request' => new Request('GET', '/about', [], [], [], [], ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https']),
        'patternBlocks' => [],
        'meta' => [],
    ]);

    expect($html)->toContain('<script type="application/ld+json" data-schema-source="template-renderer">');
    expect($html)->toContain('"@type":"WebSite"');
    expect($html)->toContain('"@type":"Organization"');
    expect($html)->toContain('"@type":"WebPage"');
    expect($html)->toContain('"name":"Example Site"');
    expect($html)->toContain('"url":"https://example.com"');
});

it('injects article and breadcrumb schema when content type and hierarchy are available', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $renderer = new TemplateRenderer($templatesBasePath, null, null, 'https://example.com', 'Example Site');

    $html = $renderer->render($templatesBasePath . '/index.php', [
        'contentItem' => makeRenderableContentItem(
            slug: 'blog-launch',
            canonicalUrl: null,
            typeName: 'article',
            metaTitle: 'Launch headline',
            metaDescription: null
        ),
        'request' => new Request('GET', '/blog-launch', [], [], [], [], ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https']),
        'patternBlocks' => [],
        'author' => ['name' => 'Editorial Team'],
        'breadcrumbs' => [
            ['name' => 'Home', 'item' => 'https://example.com/'],
            ['name' => 'Blog', 'item' => 'https://example.com/blog'],
            ['name' => 'Launch', 'item' => 'https://example.com/blog/launch'],
        ],
        'meta' => [],
    ]);

    expect($html)->toContain('"@type":"Article"');
    expect($html)->toContain('"headline":"Launch headline"');
    expect($html)->toContain('"author":{"@type":"Person","name":"Editorial Team"}');
    expect($html)->toContain('"mainEntityOfPage":"https://example.com/blog-launch"');
    expect($html)->toContain('"@type":"BreadcrumbList"');
    expect($html)->toContain('"itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://example.com/"}');
});

it('does not duplicate renderer-managed JSON-LD blocks', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $renderer = new TemplateRenderer($templatesBasePath, null, null, 'https://example.com', 'Example Site');

    $html = $renderer->render($templatesBasePath . '/index.php', [
        'contentItem' => makeRenderableContentItem('about'),
        'request' => new Request('GET', '/about', [], [], [], [], ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https']),
        'patternBlocks' => [],
        'meta' => [],
    ]);

    expect(substr_count($html, 'data-schema-source="template-renderer"'))->toBe(1);
});

/**
 * @param list<array{pattern: string, data: array<string, string>}> $patternBlocks
 */
function makeRenderableContentItem(
    string $slug,
    ?string $canonicalUrl = null,
    string $typeName = 'page',
    ?string $metaTitle = null,
    ?string $metaDescription = null,
    ?string $ogImage = null,
    array $patternBlocks = []
): ContentItem
{
    $now = new \DateTimeImmutable('2026-03-27 00:00:00');

    return new ContentItem(
        id: 1,
        type: new ContentType($typeName, ucfirst($typeName), 'content/default.php'),
        title: 'About',
        slug: Slug::fromString($slug),
        status: ContentStatus::Published,
        createdAt: $now,
        updatedAt: $now,
        patternBlocks: $patternBlocks,
        metaTitle: $metaTitle,
        metaDescription: $metaDescription,
        ogImage: $ogImage,
        canonicalUrl: $canonicalUrl
    );
}
