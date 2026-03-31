<?php

declare(strict_types=1);

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentType;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Slug;
use App\Domain\Content\Category;
use App\Domain\Content\CategoryGroup;
use App\Infrastructure\Content\MySqlCategoryGroupRepository;
use App\Infrastructure\Content\MySqlCategoryRepository;
use App\Infrastructure\Content\MySqlContentItemRepository;
use App\Infrastructure\Content\MySqlContentRelationshipRepository;
use App\Infrastructure\Content\MySqlContentTypeRepository;
use App\Infrastructure\Database\Connection;

function buildConnectionForRepositoryTests(): Connection
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec(
        'CREATE TABLE content_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT NULL,
            view_type TEXT NOT NULL DEFAULT "single",
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE content_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content_type_id INTEGER NOT NULL,
            parent_id INTEGER NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            title TEXT NOT NULL,
            meta_title TEXT NULL,
            meta_description TEXT NULL,
            og_image TEXT NULL,
            canonical_url TEXT NULL,
            noindex INTEGER NOT NULL DEFAULT 0,
            slug TEXT NOT NULL UNIQUE,
            status TEXT NOT NULL,
            body TEXT NULL,
            published_at TEXT NULL,
            pattern_blocks TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY(content_type_id) REFERENCES content_types(id),
            FOREIGN KEY(parent_id) REFERENCES content_items(id)
        )'
    );


    $pdo->exec(
        'CREATE TABLE category_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id INTEGER NOT NULL,
            parent_id INTEGER NULL,
            name TEXT NOT NULL,
            slug TEXT NOT NULL,
            description TEXT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY(group_id) REFERENCES category_groups(id),
            FOREIGN KEY(parent_id) REFERENCES categories(id),
            UNIQUE(group_id, slug)
        )'
    );

    $pdo->exec(
        'CREATE TABLE content_item_categories (
            content_item_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            PRIMARY KEY(content_item_id, category_id),
            FOREIGN KEY(content_item_id) REFERENCES content_items(id),
            FOREIGN KEY(category_id) REFERENCES categories(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE content_type_category_groups (
            content_type_id INTEGER NOT NULL,
            category_group_id INTEGER NOT NULL,
            PRIMARY KEY(content_type_id, category_group_id),
            FOREIGN KEY(content_type_id) REFERENCES content_types(id),
            FOREIGN KEY(category_group_id) REFERENCES category_groups(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE content_item_relationships (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            from_content_item_id INTEGER NOT NULL,
            to_content_item_id INTEGER NOT NULL,
            relation_type TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(from_content_item_id, to_content_item_id, relation_type),
            FOREIGN KEY(from_content_item_id) REFERENCES content_items(id),
            FOREIGN KEY(to_content_item_id) REFERENCES content_items(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE content_type_relationship_rules (
            from_content_type_id INTEGER NOT NULL,
            to_content_type_id INTEGER NOT NULL,
            relation_type TEXT NOT NULL,
            UNIQUE(from_content_type_id, to_content_type_id, relation_type),
            FOREIGN KEY(from_content_type_id) REFERENCES content_types(id),
            FOREIGN KEY(to_content_type_id) REFERENCES content_types(id)
        )'
    );

    return new Connection($pdo);
}

it('persists and reads content types', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $repository = new MySqlContentTypeRepository($connection);

    $articleType = new ContentType('article', 'Article', 'templates/pages/article.php', null, ContentViewType::COLLECTION);

    $repository->save($articleType);

    $found = $repository->findByName('article');

    expect($found)->not->toBeNull()
        ->and($found?->name())->toBe('article')
        ->and($found?->label())->toBe('Article')
        ->and($found?->defaultTemplate())->toBe('templates/pages/article.php')
        ->and($found?->viewType())->toBe(ContentViewType::COLLECTION)
        ->and($repository->findAll())->toHaveCount(1);
});

it('loads and manages allowed category groups for a content type', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $repository = new MySqlContentTypeRepository($connection);
    $groupRepository = new MySqlCategoryGroupRepository($connection);

    $contentType = new ContentType('article', 'Article', 'content/default.php');
    $repository->save($contentType);

    $blogCategories = $groupRepository->save(new CategoryGroup(
        id: null,
        name: 'Blog categories',
        slug: Slug::fromString('blog-categories'),
        description: null,
        createdAt: new DateTimeImmutable('2026-03-23 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-23 10:00:00')
    ));

    $locations = $groupRepository->save(new CategoryGroup(
        id: null,
        name: 'Locations',
        slug: Slug::fromString('locations'),
        description: null,
        createdAt: new DateTimeImmutable('2026-03-23 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-23 10:00:00')
    ));

    $repository->attachCategoryGroup($contentType, $blogCategories);
    $repository->attachCategoryGroup($contentType, $blogCategories);
    $repository->attachCategoryGroup($contentType, $locations);

    $allowedGroups = $repository->getAllowedCategoryGroups($contentType);
    $loadedType = $repository->findByName('article');

    expect($allowedGroups)->toHaveCount(2)
        ->and(array_map(static fn (CategoryGroup $group): string => $group->slug()->value(), $allowedGroups))
        ->toBe(['blog-categories', 'locations'])
        ->and($loadedType)->not->toBeNull()
        ->and($loadedType?->allowedCategoryGroupIds())->toBe([
            $blogCategories->id(),
            $locations->id(),
        ]);

    $repository->detachCategoryGroup($contentType, $blogCategories);

    expect($repository->findByName('article')?->allowedCategoryGroupIds())->toBe([$locations->id()]);
});

it('persists updates and queries content items by id slug and type', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $typeRepository = new MySqlContentTypeRepository($connection);
    $itemRepository = new MySqlContentItemRepository($connection);

    $contentType = new ContentType('article', 'Article', 'templates/pages/article.php');
    $typeRepository->save($contentType);

    $initialItem = ContentItem::draft(
        id: null,
        type: $contentType,
        title: 'Hello World',
        slug: Slug::fromString('hello-world'),
        createdAt: new DateTimeImmutable('2026-03-20 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-20 10:00:00')
    );

    $savedItem = $itemRepository->save(
        $initialItem
            ->withSeoMetadata(
                'Hello Meta',
                'Meta description for hello world',
                '/images/hello.jpg',
                'https://example.com/hello-world',
                true,
                new DateTimeImmutable('2026-03-20 10:00:00')
            )
            ->withHierarchy(null, 10, new DateTimeImmutable('2026-03-20 10:00:00'))
    );

    expect($savedItem->id())->toBeInt()
        ->and($itemRepository->findById($savedItem->id() ?? 0)?->slug()->value())->toBe('hello-world')
        ->and($itemRepository->findBySlug(Slug::fromString('hello-world'))?->title())->toBe('Hello World')
        ->and($itemRepository->findByType($contentType)['items'])->toHaveCount(1);

    $updated = $savedItem
        ->withTitle('Updated Title', new DateTimeImmutable('2026-03-21 10:00:00'))
        ->withSlug(Slug::fromString('hello-world-updated'), new DateTimeImmutable('2026-03-21 10:00:00'));

    $updatedSaved = $itemRepository->save($updated);

    expect($updatedSaved->title())->toBe('Updated Title')
        ->and($updatedSaved->slug()->value())->toBe('hello-world-updated')
        ->and($updatedSaved->metaTitle())->toBe('Hello Meta')
        ->and($updatedSaved->metaDescription())->toBe('Meta description for hello world')
        ->and($updatedSaved->ogImage())->toBe('/images/hello.jpg')
        ->and($updatedSaved->canonicalUrl())->toBe('https://example.com/hello-world')
        ->and($updatedSaved->noindex())->toBeTrue()
        ->and($updatedSaved->parentId())->toBeNull()
        ->and($updatedSaved->sortOrder())->toBe(10)
        ->and($updatedSaved->isRoot())->toBeTrue()
        ->and($updatedSaved->hasParent())->toBeFalse()
        ->and($itemRepository->findBySlug(Slug::fromString('hello-world')))->toBeNull();
});

it('loads all content items grouped by content type slug in one repository call', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $typeRepository = new MySqlContentTypeRepository($connection);
    $itemRepository = new MySqlContentItemRepository($connection);

    $pageType = new ContentType('page', 'Page', 'content/default.php');
    $articleType = new ContentType('article', 'Article', 'content/default.php');
    $typeRepository->save($pageType);
    $typeRepository->save($articleType);

    $itemRepository->save(ContentItem::draft(
        id: null,
        type: $pageType,
        title: 'About',
        slug: Slug::fromString('about'),
        createdAt: new DateTimeImmutable('2026-03-20 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-20 10:00:00')
    ));

    $itemRepository->save(ContentItem::draft(
        id: null,
        type: $articleType,
        title: 'News',
        slug: Slug::fromString('news'),
        createdAt: new DateTimeImmutable('2026-03-21 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-21 10:00:00')
    ));

    $grouped = $itemRepository->findAllWithTypes();

    expect($grouped)->toHaveKeys(['items', 'total_count', 'limit', 'offset'])
        ->and($grouped['items'])->toHaveKeys(['article', 'page'])
        ->and($grouped['items']['page'])->toHaveCount(1)
        ->and($grouped['items']['page'][0]->slug()->value())->toBe('about')
        ->and($grouped['items']['article'])->toHaveCount(1)
        ->and($grouped['items']['article'][0]->slug()->value())->toBe('news');
});

it('finds root items and children using hierarchy fields', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $typeRepository = new MySqlContentTypeRepository($connection);
    $itemRepository = new MySqlContentItemRepository($connection);

    $pageType = new ContentType('page', 'Page', 'content/default.php');
    $typeRepository->save($pageType);

    $rootA = $itemRepository->save(
        ContentItem::draft(
            id: null,
            type: $pageType,
            title: 'Root A',
            slug: Slug::fromString('root-a'),
            createdAt: new DateTimeImmutable('2026-03-20 10:00:00'),
            updatedAt: new DateTimeImmutable('2026-03-20 10:00:00')
        )->withHierarchy(null, 2, new DateTimeImmutable('2026-03-20 10:00:00'))
    );

    $rootB = $itemRepository->save(
        ContentItem::draft(
            id: null,
            type: $pageType,
            title: 'Root B',
            slug: Slug::fromString('root-b'),
            createdAt: new DateTimeImmutable('2026-03-20 10:00:00'),
            updatedAt: new DateTimeImmutable('2026-03-20 10:00:00')
        )->withHierarchy(null, 1, new DateTimeImmutable('2026-03-20 10:00:00'))
    );

    $itemRepository->save(
        ContentItem::draft(
            id: null,
            type: $pageType,
            title: 'Child B1',
            slug: Slug::fromString('child-b1'),
            createdAt: new DateTimeImmutable('2026-03-20 10:00:00'),
            updatedAt: new DateTimeImmutable('2026-03-20 10:00:00')
        )->withHierarchy($rootB->id(), 2, new DateTimeImmutable('2026-03-20 10:00:00'))
    );

    $itemRepository->save(
        ContentItem::draft(
            id: null,
            type: $pageType,
            title: 'Child B0',
            slug: Slug::fromString('child-b0'),
            createdAt: new DateTimeImmutable('2026-03-20 10:00:00'),
            updatedAt: new DateTimeImmutable('2026-03-20 10:00:00')
        )->withHierarchy($rootB->id(), 1, new DateTimeImmutable('2026-03-20 10:00:00'))
    );

    expect($rootA->id())->not->toBeNull()
        ->and($rootB->id())->not->toBeNull();

    $rootItems = $itemRepository->findRootItems();
    $children = $itemRepository->findChildrenOf($rootB->id() ?? 0);

    expect(array_map(static fn (ContentItem $item): string => $item->slug()->value(), $rootItems))
        ->toBe(['root-b', 'root-a'])
        ->and(array_map(static fn (ContentItem $item): string => $item->slug()->value(), $children))
        ->toBe(['child-b0', 'child-b1'])
        ->and($children[0]->hasParent())->toBeTrue()
        ->and($children[0]->parentId())->toBe($rootB->id());
});


it('persists and queries category groups and hierarchical categories', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $groupRepository = new MySqlCategoryGroupRepository($connection);
    $categoryRepository = new MySqlCategoryRepository($connection);

    $group = $groupRepository->save(new CategoryGroup(
        id: null,
        name: 'Locations',
        slug: Slug::fromString('locations'),
        description: 'Location grouping',
        createdAt: new DateTimeImmutable('2026-03-22 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-22 10:00:00')
    ));

    $root = $categoryRepository->save(new Category(
        id: null,
        groupId: $group->id() ?? 0,
        parentId: null,
        name: 'Kirkenes',
        slug: Slug::fromString('kirkenes'),
        description: null,
        sortOrder: 1,
        createdAt: new DateTimeImmutable('2026-03-22 10:01:00'),
        updatedAt: new DateTimeImmutable('2026-03-22 10:01:00')
    ));

    $child = $categoryRepository->save(new Category(
        id: null,
        groupId: $group->id() ?? 0,
        parentId: $root->id(),
        name: 'Harbor',
        slug: Slug::fromString('harbor'),
        description: null,
        sortOrder: 2,
        createdAt: new DateTimeImmutable('2026-03-22 10:02:00'),
        updatedAt: new DateTimeImmutable('2026-03-22 10:02:00')
    ));

    expect($groupRepository->findAllGroups())->toHaveCount(1)
        ->and($groupRepository->findBySlug('locations')?->name())->toBe('Locations')
        ->and($categoryRepository->findBySlugInGroup($group, 'kirkenes')?->name())->toBe('Kirkenes')
        ->and($categoryRepository->findCategoriesByGroup($group))->toHaveCount(2)
        ->and($categoryRepository->findRootCategoriesByGroup($group))->toHaveCount(1)
        ->and($categoryRepository->findRootCategoriesByGroup($group)[0]->name())->toBe('Kirkenes')
        ->and($categoryRepository->findChildrenOf($root))->toHaveCount(1)
        ->and($categoryRepository->findChildrenOf($root)[0]->name())->toBe('Harbor')
        ->and($child->parentId())->toBe($root->id());
});

it('attaches and detaches categories on content items', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $typeRepository = new MySqlContentTypeRepository($connection);
    $itemRepository = new MySqlContentItemRepository($connection);
    $groupRepository = new MySqlCategoryGroupRepository($connection);
    $categoryRepository = new MySqlCategoryRepository($connection);

    $type = new ContentType('article', 'Article', 'content/default.php');
    $typeRepository->save($type);

    $item = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $type,
        title: 'News post',
        slug: Slug::fromString('news-post'),
        createdAt: new DateTimeImmutable('2026-03-23 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-23 10:00:00')
    ));

    $group = $groupRepository->save(new CategoryGroup(
        id: null,
        name: 'Blog categories',
        slug: Slug::fromString('blog-categories'),
        description: null,
        createdAt: new DateTimeImmutable('2026-03-23 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-23 10:00:00')
    ));

    $category = $categoryRepository->save(new Category(
        id: null,
        groupId: $group->id() ?? 0,
        parentId: null,
        name: 'News',
        slug: Slug::fromString('news'),
        description: null,
        sortOrder: 0,
        createdAt: new DateTimeImmutable('2026-03-23 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-23 10:00:00')
    ));

    $typeRepository->attachCategoryGroup($type, $group);
    $categoryRepository->attachCategoryToContentItem($item, $category);
    $categoryRepository->attachCategoryToContentItem($item, $category);

    $attached = $categoryRepository->findCategoriesForContentItem($item);
    $publishedForCategory = $itemRepository->findPublishedByCategory($category);

    expect($attached)->toHaveCount(1)
        ->and($attached[0]->name())->toBe('News')
        ->and($publishedForCategory['items'])->toHaveCount(0);

    $published = $item->publish(new DateTimeImmutable('2026-03-23 10:01:00'));
    $itemRepository->save($published);
    $publishedForCategory = $itemRepository->findPublishedByCategory($category);

    expect($publishedForCategory['items'])->toHaveCount(1)
        ->and($publishedForCategory['items'][0]->slug()->value())->toBe('news-post');

    $categoryRepository->detachCategoryFromContentItem($item, $category);

    expect($categoryRepository->findCategoriesForContentItem($item))->toHaveCount(0);
});

it('rejects attaching categories from category groups not allowed by content type', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $typeRepository = new MySqlContentTypeRepository($connection);
    $itemRepository = new MySqlContentItemRepository($connection);
    $groupRepository = new MySqlCategoryGroupRepository($connection);
    $categoryRepository = new MySqlCategoryRepository($connection);

    $type = new ContentType('event', 'Event', 'content/default.php');
    $typeRepository->save($type);

    $item = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $type,
        title: 'Expo',
        slug: Slug::fromString('expo'),
        createdAt: new DateTimeImmutable('2026-03-23 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-23 10:00:00')
    ));

    $group = $groupRepository->save(new CategoryGroup(
        id: null,
        name: 'Product categories',
        slug: Slug::fromString('product-categories'),
        description: null,
        createdAt: new DateTimeImmutable('2026-03-23 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-23 10:00:00')
    ));

    $category = $categoryRepository->save(new Category(
        id: null,
        groupId: $group->id() ?? 0,
        parentId: null,
        name: 'Widgets',
        slug: Slug::fromString('widgets'),
        description: null,
        sortOrder: 0,
        createdAt: new DateTimeImmutable('2026-03-23 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-23 10:00:00')
    ));

    expect(static fn () => $categoryRepository->attachCategoryToContentItem($item, $category))
        ->toThrow(RuntimeException::class, 'Cannot attach category from a group that is not allowed for this content type.');
});

it('attaches finds and detaches content relationships independently of hierarchy and categories', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $typeRepository = new MySqlContentTypeRepository($connection);
    $itemRepository = new MySqlContentItemRepository($connection);
    $relationshipRepository = new MySqlContentRelationshipRepository($connection);

    $articleType = new ContentType('article', 'Article', 'content/default.php');
    $authorType = new ContentType('author', 'Author', 'content/default.php');
    $typeRepository->save($articleType);
    $typeRepository->save($authorType);

    $article = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $articleType,
        title: 'Launch Update',
        slug: Slug::fromString('launch-update'),
        createdAt: new DateTimeImmutable('2026-03-24 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-24 10:00:00')
    ));

    $author = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $authorType,
        title: 'Jane Doe',
        slug: Slug::fromString('jane-doe'),
        createdAt: new DateTimeImmutable('2026-03-24 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-24 10:00:00')
    ));

    $relatedArticle = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $articleType,
        title: 'Roadmap Follow-up',
        slug: Slug::fromString('roadmap-follow-up'),
        createdAt: new DateTimeImmutable('2026-03-24 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-24 10:00:00')
    ));

    $relationshipRepository->allowRelationship($articleType, $authorType, 'author');
    $relationshipRepository->allowRelationship($articleType, $articleType, 'related');

    $relationshipRepository->attach($article, $author, 'author', 10);
    $relationshipRepository->attach($article, $relatedArticle, 'related', 20);
    $relationshipRepository->attach($article, $relatedArticle, 'related', 20);

    $outgoing = $relationshipRepository->findOutgoingRelationships($article);
    $incoming = $relationshipRepository->findIncomingRelationships($author);
    $typed = $relationshipRepository->findByType($article, 'related');

    expect($outgoing)->toHaveCount(2)
        ->and($incoming)->toHaveCount(1)
        ->and($incoming[0]->fromContentItemId())->toBe($article->id())
        ->and($incoming[0]->toContentItemId())->toBe($author->id())
        ->and($incoming[0]->fromContentItemTitle())->toBe('Launch Update')
        ->and($incoming[0]->toContentItemTitle())->toBe('Jane Doe')
        ->and($incoming[0]->fromContentTypeSlug())->toBe('article')
        ->and($incoming[0]->toContentTypeSlug())->toBe('author')
        ->and($typed)->toHaveCount(1)
        ->and($typed[0]->toContentItemId())->toBe($relatedArticle->id())
        ->and($typed[0]->relationType())->toBe('related')
        ->and($typed[0]->sortOrder())->toBe(20);

    $relationshipRepository->detach($article, $relatedArticle, 'related');

    expect($relationshipRepository->findByType($article, 'related'))->toHaveCount(0);
});

it('rejects invalid content relationships', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $typeRepository = new MySqlContentTypeRepository($connection);
    $itemRepository = new MySqlContentItemRepository($connection);
    $relationshipRepository = new MySqlContentRelationshipRepository($connection);

    $type = new ContentType('page', 'Page', 'content/default.php');
    $authorType = new ContentType('author', 'Author', 'content/default.php');
    $typeRepository->save($type);
    $typeRepository->save($authorType);

    $item = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $type,
        title: 'About',
        slug: Slug::fromString('about-page'),
        createdAt: new DateTimeImmutable('2026-03-25 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-25 10:00:00')
    ));

    expect(fn (): array => $relationshipRepository->findByType($item, '   '))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $relationshipRepository->attach($item, $item, 'featuredcase'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn (): array => $relationshipRepository->findByType($item, 'related_article'))
        ->toThrow(InvalidArgumentException::class, 'lowercase letters only');

    expect(fn (): bool => $relationshipRepository->isRelationshipAllowed($type, $authorType, str_repeat('a', 61)))
        ->toThrow(InvalidArgumentException::class, '60 characters or fewer');

    $author = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $authorType,
        title: 'Ada Lovelace',
        slug: Slug::fromString('ada-lovelace'),
        createdAt: new DateTimeImmutable('2026-03-25 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-25 10:00:00')
    ));

    expect(fn () => $relationshipRepository->attach($item, $author, 'author'))
        ->toThrow(InvalidArgumentException::class, 'is not allowed');
});


it('fails fast when allowing a relationship for unsaved content types', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $relationshipRepository = new MySqlContentRelationshipRepository($connection);

    $missingFrom = new ContentType('missing_from', 'Missing From', 'content/default.php');
    $missingTo = new ContentType('missing_to', 'Missing To', 'content/default.php');

    expect(fn () => $relationshipRepository->allowRelationship($missingFrom, $missingTo, 'author'))
        ->toThrow(RuntimeException::class, 'Content type "missing_from" does not exist in persistence.');
});

it('fails fast when attaching a relationship to a missing target item id', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $typeRepository = new MySqlContentTypeRepository($connection);
    $itemRepository = new MySqlContentItemRepository($connection);
    $relationshipRepository = new MySqlContentRelationshipRepository($connection);

    $articleType = new ContentType('article', 'Article', 'content/default.php');
    $authorType = new ContentType('author', 'Author', 'content/default.php');
    $typeRepository->save($articleType);
    $typeRepository->save($authorType);
    $relationshipRepository->allowRelationship($articleType, $authorType, 'author');

    $article = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $articleType,
        title: 'Launch Update',
        slug: Slug::fromString('launch-update-missing-target'),
        createdAt: new DateTimeImmutable('2026-03-25 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-25 10:00:00')
    ));

    $missingAuthor = ContentItem::draft(
        id: 999,
        type: $authorType,
        title: 'Ghost Author',
        slug: Slug::fromString('ghost-author'),
        createdAt: new DateTimeImmutable('2026-03-25 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-25 10:00:00')
    );

    expect(fn () => $relationshipRepository->attach($article, $missingAuthor, 'author'))
        ->toThrow(RuntimeException::class, 'Content item 999 does not exist in persistence.');
});
it('manages relationship rules by content type and enforces them at attach time', function (): void {
    $connection = buildConnectionForRepositoryTests();
    $typeRepository = new MySqlContentTypeRepository($connection);
    $itemRepository = new MySqlContentItemRepository($connection);
    $relationshipRepository = new MySqlContentRelationshipRepository($connection);

    $articleType = new ContentType('article', 'Article', 'content/default.php');
    $authorType = new ContentType('author', 'Author', 'content/default.php');
    $eventType = new ContentType('event', 'Event', 'content/default.php');
    $typeRepository->save($articleType);
    $typeRepository->save($authorType);
    $typeRepository->save($eventType);

    expect($relationshipRepository->isRelationshipAllowed($articleType, $authorType, 'author'))->toBeFalse();

    $relationshipRepository->allowRelationship($articleType, $authorType, 'author');
    $relationshipRepository->allowRelationship($articleType, $authorType, 'author');

    expect($relationshipRepository->isRelationshipAllowed($articleType, $authorType, 'author'))->toBeTrue()
        ->and($relationshipRepository->isRelationshipAllowed($articleType, $eventType, 'author'))->toBeFalse();

    $article = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $articleType,
        title: 'Blueprint Launch',
        slug: Slug::fromString('blueprint-launch'),
        createdAt: new DateTimeImmutable('2026-03-25 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-25 10:00:00')
    ));
    $author = $itemRepository->save(ContentItem::draft(
        id: null,
        type: $authorType,
        title: 'Author A',
        slug: Slug::fromString('author-a'),
        createdAt: new DateTimeImmutable('2026-03-25 10:00:00'),
        updatedAt: new DateTimeImmutable('2026-03-25 10:00:00')
    ));

    $relationshipRepository->attach($article, $author, 'author');
    expect($relationshipRepository->findByType($article, 'author'))->toHaveCount(1);

    $relationshipRepository->removeRelationshipRule($articleType, $authorType, 'author');

    expect($relationshipRepository->isRelationshipAllowed($articleType, $authorType, 'author'))->toBeFalse()
        ->and(fn () => $relationshipRepository->attach($article, $author, 'author'))
        ->toThrow(InvalidArgumentException::class, 'is not allowed');
});
