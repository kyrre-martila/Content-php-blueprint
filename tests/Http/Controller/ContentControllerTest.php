<?php

declare(strict_types=1);

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Slug;
use App\Http\Controller\ContentController;
use App\Http\Request;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

it('redirects non-canonical content paths with a 301 and preserves query parameters', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $contentItem = makeContentItem('about');
    $controller = new ContentController(
        repositoryWithSingleItem($contentItem),
        new TemplateResolver($templatesBasePath),
        new TemplateRenderer($templatesBasePath, null, null, 'https://example.com'),
        editorModeForTests()
    );

    $request = new Request(
        'GET',
        '/about',
        ['utm' => 'test'],
        [],
        [],
        [],
        ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https'],
        ['slug' => 'about'],
        '/About/'
    );

    $response = $controller->show($request);

    expect($response->status())->toBe(301)
        ->and($response->header('Location'))->toBe('/about?utm=test');
});

it('uses canonical_url metadata as the redirect target when defined', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $contentItem = makeContentItem('about', 'https://example.com/about');
    $controller = new ContentController(
        repositoryWithSingleItem($contentItem),
        new TemplateResolver($templatesBasePath),
        new TemplateRenderer($templatesBasePath, null, null, 'https://example.com'),
        editorModeForTests()
    );

    $request = new Request(
        'GET',
        '/about',
        [],
        [],
        [],
        [],
        ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https'],
        ['slug' => 'about'],
        '/about/index'
    );

    $response = $controller->show($request);

    expect($response->status())->toBe(301)
        ->and($response->header('Location'))->toBe('https://example.com/about');
});

it('does not redirect when the request is already canonical', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $contentItem = makeContentItem('about', 'https://example.com/about');
    $controller = new ContentController(
        repositoryWithSingleItem($contentItem),
        new TemplateResolver($templatesBasePath),
        new TemplateRenderer($templatesBasePath, null, null, 'https://example.com'),
        editorModeForTests()
    );

    $request = new Request(
        'GET',
        '/about',
        [],
        [],
        [],
        [],
        ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https'],
        ['slug' => 'about'],
        '/about'
    );

    $response = $controller->show($request);

    expect($response->status())->toBe(200);
});

it('renders collection template for content types configured as collection view', function (): void {
    $templatesBasePath = sys_get_temp_dir() . '/content-blueprint-content-controller-' . uniqid('', true);
    mkdir($templatesBasePath . '/content', 0777, true);
    mkdir($templatesBasePath . '/collections', 0777, true);
    mkdir($templatesBasePath . '/system', 0777, true);
    file_put_contents($templatesBasePath . '/content/page.php', '<?php echo "single-template";');
    file_put_contents($templatesBasePath . '/collections/page.php', '<?php echo "collection-template";');
    file_put_contents($templatesBasePath . '/system/404.php', '<?php echo "not-found";');

    $contentItem = makeContentItem('about', null, ContentViewType::COLLECTION);
    $controller = new ContentController(
        repositoryWithSingleItem($contentItem),
        new TemplateResolver($templatesBasePath),
        new TemplateRenderer($templatesBasePath, null, null, 'https://example.com'),
        editorModeForTests()
    );

    $request = new Request(
        'GET',
        '/about',
        [],
        [],
        [],
        [],
        ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https'],
        ['slug' => 'about'],
        '/about'
    );

    $response = $controller->show($request);

    expect($response->status())->toBe(200)
        ->and($response->body())->toContain('collection-template');
});

function makeContentItem(
    string $slug,
    ?string $canonicalUrl = null,
    ContentViewType $viewType = ContentViewType::SINGLE
): ContentItem
{
    $now = new \DateTimeImmutable('2026-03-27 00:00:00');

    return new ContentItem(
        id: 1,
        type: new ContentType('page', 'Page', 'content/default.php', null, $viewType),
        title: 'About',
        slug: Slug::fromString($slug),
        status: ContentStatus::Published,
        createdAt: $now,
        updatedAt: $now,
        patternBlocks: [],
        canonicalUrl: $canonicalUrl
    );
}

function repositoryWithSingleItem(ContentItem $contentItem): ContentItemRepositoryInterface
{
    return new class ($contentItem) implements ContentItemRepositoryInterface {
        public function __construct(private readonly ContentItem $contentItem)
        {
        }

        public function save(ContentItem $contentItem): ContentItem
        {
            return $contentItem;
        }

        public function findById(int $id): ?ContentItem
        {
            return $id === 1 ? $this->contentItem : null;
        }

        public function findBySlug(Slug $slug): ?ContentItem
        {
            return $slug->value() === $this->contentItem->slug()->value() ? $this->contentItem : null;
        }

        public function findByType(ContentType $contentType): array
        {
            return [];
        }

        public function findPublished(): array
        {
            return $this->contentItem->isPublished() ? [$this->contentItem] : [];
        }

        public function remove(ContentItem $contentItem): void
        {
        }
    };
}

function editorModeForTests(): EditorMode
{
    $session = new SessionManager([
        'name' => 'content_blueprint_test_session',
        'secure_cookie' => false,
    ]);

    return new EditorMode(new AuthSession($session), $session);
}
