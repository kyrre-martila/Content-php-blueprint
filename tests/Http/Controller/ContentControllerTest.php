<?php

declare(strict_types=1);

use App\Domain\Content\Category;
use App\Domain\Content\CategoryGroup;
use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Repository\CategoryGroupRepositoryInterface;
use App\Domain\Content\Repository\CategoryRepositoryInterface;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Slug;
use App\Domain\Files\FileAsset;
use App\Domain\Files\Repository\FileRepositoryInterface;
use App\Http\Controller\ContentController;
use App\Http\Request;
use App\Application\Files\ContentItemFileFieldResolver;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplatePathMap;
use App\Infrastructure\View\TemplateResolver;

it('redirects non-canonical content paths with a 301 and preserves query parameters', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';
    $contentItem = makeContentItem('about');
    $controller = new ContentController(
        categoryGroupRepositoryForTests(),
        categoryRepositoryForTests(),
        repositoryWithSingleItem($contentItem),
        fileFieldResolverForTests(),
        new TemplateResolver($templatesBasePath, new TemplatePathMap()),
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
        categoryGroupRepositoryForTests(),
        categoryRepositoryForTests(),
        repositoryWithSingleItem($contentItem),
        fileFieldResolverForTests(),
        new TemplateResolver($templatesBasePath, new TemplatePathMap()),
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
        categoryGroupRepositoryForTests(),
        categoryRepositoryForTests(),
        repositoryWithSingleItem($contentItem),
        fileFieldResolverForTests(),
        new TemplateResolver($templatesBasePath, new TemplatePathMap()),
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
        categoryGroupRepositoryForTests(),
        categoryRepositoryForTests(),
        repositoryWithSingleItem($contentItem),
        fileFieldResolverForTests(),
        new TemplateResolver($templatesBasePath, new TemplatePathMap()),
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



it('passes collection items and pagination data to collection templates', function (): void {
    $templatesBasePath = sys_get_temp_dir() . '/content-blueprint-content-controller-pagination-' . uniqid('', true);
    mkdir($templatesBasePath . '/collections', 0777, true);
    mkdir($templatesBasePath . '/system', 0777, true);
    file_put_contents($templatesBasePath . '/collections/page.php', '<?php echo count($collectionItems) . "|" . $pagination["currentPage"] . "|" . $pagination["perPage"] . "|" . $pagination["totalCount"] . "|" . $pagination["totalPages"];');
    file_put_contents($templatesBasePath . '/system/404.php', '<?php echo "not-found";');

    $contentItem = makeContentItem('about', null, ContentViewType::COLLECTION);
    $controller = new ContentController(
        categoryGroupRepositoryForTests(),
        categoryRepositoryForTests(),
        repositoryWithCollectionItems($contentItem, [makeContentItem('post-1'), makeContentItem('post-2')]),
        fileFieldResolverForTests(),
        new TemplateResolver($templatesBasePath, new TemplatePathMap()),
        new TemplateRenderer($templatesBasePath, null, null, 'https://example.com'),
        editorModeForTests()
    );

    $response = $controller->show(new Request(
        'GET',
        '/about',
        ['page' => '2', 'perPage' => '1'],
        [],
        [],
        [],
        ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https'],
        ['slug' => 'about'],
        '/about'
    ));

    expect($response->status())->toBe(200)
        ->and($response->body())->toContain('2|2|1|2|2');
});

it('renders collection templates with empty collection data instead of 404', function (): void {
    $templatesBasePath = sys_get_temp_dir() . '/content-blueprint-content-controller-empty-collection-' . uniqid('', true);
    mkdir($templatesBasePath . '/collections', 0777, true);
    mkdir($templatesBasePath . '/system', 0777, true);
    file_put_contents($templatesBasePath . '/collections/page.php', '<?php echo count($collectionItems) . "|" . $pagination["totalCount"];');
    file_put_contents($templatesBasePath . '/system/404.php', '<?php echo "not-found";');

    $contentItem = makeContentItem('about', null, ContentViewType::COLLECTION);
    $controller = new ContentController(
        categoryGroupRepositoryForTests(),
        categoryRepositoryForTests(),
        repositoryWithCollectionItems($contentItem, []),
        fileFieldResolverForTests(),
        new TemplateResolver($templatesBasePath, new TemplatePathMap()),
        new TemplateRenderer($templatesBasePath, null, null, 'https://example.com'),
        editorModeForTests()
    );

    $response = $controller->show(new Request(
        'GET',
        '/about',
        [],
        [],
        [],
        [],
        ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https'],
        ['slug' => 'about'],
        '/about'
    ));

    expect($response->status())->toBe(200)
        ->and($response->body())->toContain('0|0')
        ->and($response->body())->not->toContain('not-found');
});
it('renders category collection pages with category context and breadcrumbs', function (): void {
    $templatesBasePath = sys_get_temp_dir() . '/content-blueprint-category-controller-' . uniqid('', true);
    mkdir($templatesBasePath . '/collections/categories', 0777, true);
    mkdir($templatesBasePath . '/system', 0777, true);
    file_put_contents($templatesBasePath . '/collections/categories/blog.php', '<?php echo $categoryGroup->name() . "|" . $category->name() . "|" . count($collectionItems) . "|" . $pagination["totalCount"] . "|" . $breadcrumbs[2]["url"];');
    file_put_contents($templatesBasePath . '/system/404.php', '<?php echo "not-found";');

    $group = makeCategoryGroup('blog', 'Blog');
    $category = makeCategory($group, 'news', 'News');
    $items = [makeContentItem('news-1'), makeContentItem('news-2')];

    $controller = new ContentController(
        categoryGroupRepositoryForTests([$group]),
        categoryRepositoryForTests([$category]),
        repositoryWithCategoryItems($items),
        fileFieldResolverForTests(),
        new TemplateResolver($templatesBasePath, new TemplatePathMap()),
        new TemplateRenderer($templatesBasePath, null, null, 'https://example.com'),
        editorModeForTests()
    );

    $response = $controller->showCategoryCollection(new Request(
        'GET',
        '/categories/blog/news',
        [],
        [],
        [],
        [],
        ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https'],
        ['groupSlug' => 'blog', 'categorySlug' => 'news'],
        '/categories/blog/news'
    ));

    expect($response->status())->toBe(200)
        ->and($response->body())->toContain('Blog|News|2|2|/categories/blog/news');
});

it('renders category collection pages with empty state data and no 404', function (): void {
    $templatesBasePath = sys_get_temp_dir() . '/content-blueprint-category-controller-empty-' . uniqid('', true);
    mkdir($templatesBasePath . '/collections/categories', 0777, true);
    mkdir($templatesBasePath . '/system', 0777, true);
    file_put_contents($templatesBasePath . '/collections/categories/blog.php', '<?php echo count($collectionItems) . "|" . $pagination["totalCount"];');
    file_put_contents($templatesBasePath . '/system/404.php', '<?php echo "not-found";');

    $group = makeCategoryGroup('blog', 'Blog');
    $category = makeCategory($group, 'empty', 'Empty');

    $controller = new ContentController(
        categoryGroupRepositoryForTests([$group]),
        categoryRepositoryForTests([$category]),
        repositoryWithCategoryItems([]),
        fileFieldResolverForTests(),
        new TemplateResolver($templatesBasePath, new TemplatePathMap()),
        new TemplateRenderer($templatesBasePath, null, null, 'https://example.com'),
        editorModeForTests()
    );

    $response = $controller->showCategoryCollection(new Request(
        'GET',
        '/categories/blog/empty',
        [],
        [],
        [],
        [],
        ['HTTP_HOST' => 'example.com', 'REQUEST_SCHEME' => 'https'],
        ['groupSlug' => 'blog', 'categorySlug' => 'empty'],
        '/categories/blog/empty'
    ));

    expect($response->status())->toBe(200)
        ->and($response->body())->toContain('0|0')
        ->and($response->body())->not->toContain('not-found');
});

it('returns system 404 when category group or category does not exist', function (): void {
    $templatesBasePath = sys_get_temp_dir() . '/content-blueprint-category-controller-404-' . uniqid('', true);
    mkdir($templatesBasePath . '/collections/categories', 0777, true);
    mkdir($templatesBasePath . '/system', 0777, true);
    file_put_contents($templatesBasePath . '/collections/categories/blog.php', '<?php echo "ok";');
    file_put_contents($templatesBasePath . '/system/404.php', '<?php echo "not-found";');

    $group = makeCategoryGroup('blog', 'Blog');
    $category = makeCategory($group, 'news', 'News');

    $controller = new ContentController(
        categoryGroupRepositoryForTests([$group]),
        categoryRepositoryForTests([$category]),
        repositoryWithCategoryItems([]),
        fileFieldResolverForTests(),
        new TemplateResolver($templatesBasePath, new TemplatePathMap()),
        new TemplateRenderer($templatesBasePath, null, null, 'https://example.com'),
        editorModeForTests()
    );

    $missingGroup = $controller->showCategoryCollection(new Request(
        'GET',
        '/categories/missing/news',
        [],
        [],
        [],
        [],
        [],
        ['groupSlug' => 'missing', 'categorySlug' => 'news'],
        '/categories/missing/news'
    ));

    $missingCategory = $controller->showCategoryCollection(new Request(
        'GET',
        '/categories/blog/missing',
        [],
        [],
        [],
        [],
        [],
        ['groupSlug' => 'blog', 'categorySlug' => 'missing'],
        '/categories/blog/missing'
    ));

    expect($missingGroup->status())->toBe(404)
        ->and($missingGroup->body())->toContain('not-found')
        ->and($missingCategory->status())->toBe(404)
        ->and($missingCategory->body())->toContain('not-found');
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

function makeCategoryGroup(string $slug, string $name): CategoryGroup
{
    return new CategoryGroup(
        id: 1,
        name: $name,
        slug: Slug::fromString($slug),
        description: null,
        createdAt: new DateTimeImmutable('2026-03-27 00:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-27 00:00:00'),
    );
}

function makeCategory(CategoryGroup $group, string $slug, string $name): Category
{
    return new Category(
        id: 1,
        groupId: $group->id() ?? 1,
        parentId: null,
        name: $name,
        slug: Slug::fromString($slug),
        description: null,
        sortOrder: 0,
        createdAt: new DateTimeImmutable('2026-03-27 00:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-27 00:00:00'),
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

        public function findByType(ContentType $contentType, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
        {
            return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset];
        }

        public function findAllWithTypes(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
        {
            return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset];
        }

        public function findPublished(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
        {
            $items = $this->contentItem->isPublished() ? [$this->contentItem] : [];

            return ['items' => $items, 'total_count' => count($items), 'limit' => $limit, 'offset' => $offset];
        }

        public function findPublishedByType(ContentType $contentType, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
        {
            return ['items' => [$this->contentItem], 'total_count' => 1, 'limit' => $limit, 'offset' => $offset];
        }

        public function findPublishedByCategory(Category $category, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array
        {
            return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset];
        }

        public function findChildrenOf(int $parentId): array
        {
            return [];
        }

        public function findRootItems(): array
        {
            return [];
        }

        public function remove(ContentItem $contentItem): void
        {
        }
    };
}



/**
 * @param list<ContentItem> $collectionItems
 */
function repositoryWithCollectionItems(ContentItem $contentItem, array $collectionItems): ContentItemRepositoryInterface
{
    return new class ($contentItem, $collectionItems) implements ContentItemRepositoryInterface {
        /** @param list<ContentItem> $collectionItems */
        public function __construct(private readonly ContentItem $contentItem, private readonly array $collectionItems)
        {
        }

        public function save(ContentItem $contentItem): ContentItem { return $contentItem; }
        public function findById(int $id): ?ContentItem { return $id === 1 ? $this->contentItem : null; }
        public function findBySlug(Slug $slug): ?ContentItem { return $slug->value() === $this->contentItem->slug()->value() ? $this->contentItem : null; }
        public function findChildrenOf(int $parentId): array { return []; }
        public function findRootItems(): array { return []; }
        public function findByType(ContentType $contentType, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset]; }
        public function findAllWithTypes(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset]; }
        public function findPublished(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [$this->contentItem], 'total_count' => 1, 'limit' => $limit, 'offset' => $offset]; }
        public function findPublishedByType(ContentType $contentType, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => $this->collectionItems, 'total_count' => count($this->collectionItems), 'limit' => $limit, 'offset' => $offset]; }
        public function findPublishedByCategory(Category $category, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset]; }
        public function remove(ContentItem $contentItem): void {}
    };
}
/**
 * @param list<ContentItem> $items
 */
function repositoryWithCategoryItems(array $items): ContentItemRepositoryInterface
{
    return new class ($items) implements ContentItemRepositoryInterface {
        /** @param list<ContentItem> $items */
        public function __construct(private readonly array $items)
        {
        }

        public function save(ContentItem $contentItem): ContentItem { return $contentItem; }
        public function findById(int $id): ?ContentItem { return null; }
        public function findBySlug(Slug $slug): ?ContentItem { return null; }
        public function findChildrenOf(int $parentId): array { return []; }
        public function findRootItems(): array { return []; }
        public function findByType(ContentType $contentType, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset]; }
        public function findAllWithTypes(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => [], 'total_count' => 0, 'limit' => $limit, 'offset' => $offset]; }
        public function findPublished(int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => $this->items, 'total_count' => count($this->items), 'limit' => $limit, 'offset' => $offset]; }
        public function findPublishedByType(ContentType $contentType, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => $this->items, 'total_count' => count($this->items), 'limit' => $limit, 'offset' => $offset]; }
        public function findPublishedByCategory(Category $category, int $limit = self::DEFAULT_LIMIT, int $offset = self::DEFAULT_OFFSET): array { return ['items' => $this->items, 'total_count' => count($this->items), 'limit' => $limit, 'offset' => $offset]; }
        public function remove(ContentItem $contentItem): void {}
    };
}

/**
 * @param list<CategoryGroup> $groups
 */
function categoryGroupRepositoryForTests(array $groups = []): CategoryGroupRepositoryInterface
{
    return new class ($groups) implements CategoryGroupRepositoryInterface {
        /** @param list<CategoryGroup> $groups */
        public function __construct(private readonly array $groups)
        {
        }

        public function save(CategoryGroup $group): CategoryGroup { return $group; }
        public function findById(int $id): ?CategoryGroup { return null; }
        public function findBySlug(string $slug): ?CategoryGroup
        {
            foreach ($this->groups as $group) {
                if ($group->slug()->value() === $slug) {
                    return $group;
                }
            }

            return null;
        }
        public function findAllGroups(): array { return $this->groups; }
        public function remove(CategoryGroup $group): void {}
        public function isInUse(CategoryGroup $group): bool { return false; }
    };
}

/**
 * @param list<Category> $categories
 */
function categoryRepositoryForTests(array $categories = []): CategoryRepositoryInterface
{
    return new class ($categories) implements CategoryRepositoryInterface {
        /** @param list<Category> $categories */
        public function __construct(private readonly array $categories)
        {
        }

        public function save(Category $category): Category { return $category; }
        public function findById(int $id): ?Category { return null; }
        public function findBySlugInGroup(CategoryGroup $group, string $slug): ?Category
        {
            foreach ($this->categories as $category) {
                if ($category->groupId() === $group->id() && $category->slug()->value() === $slug) {
                    return $category;
                }
            }

            return null;
        }
        public function findCategoriesByGroup(CategoryGroup $group): array { return []; }
        public function findRootCategoriesByGroup(CategoryGroup $group): array { return []; }
        public function findChildrenOf(Category $category): array { return []; }
        public function findCategoriesForContentItem(ContentItem $item): array { return []; }
        public function attachCategoryToContentItem(ContentItem $item, Category $category): void {}
        public function detachCategoryFromContentItem(ContentItem $item, Category $category): void {}
        public function remove(Category $category): void {}
        public function isAssignedToContentItems(Category $category): bool { return false; }
        public function hasChildren(Category $category): bool { return false; }
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

function fileFieldResolverForTests(): ContentItemFileFieldResolver
{
    return new ContentItemFileFieldResolver(new class implements FileRepositoryInterface {
        public function save(FileAsset $fileAsset): FileAsset
        {
            return $fileAsset;
        }

        public function findById(int $id): ?FileAsset
        {
            return null;
        }

        public function findBySlug(string $slug): ?FileAsset
        {
            return null;
        }

        public function findAll(): array
        {
            return [];
        }

        public function delete(FileAsset $fileAsset): void
        {
        }
    });
}
