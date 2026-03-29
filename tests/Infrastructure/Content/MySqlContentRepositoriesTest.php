<?php

declare(strict_types=1);

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentType;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Slug;
use App\Infrastructure\Content\MySqlContentItemRepository;
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
            FOREIGN KEY(content_type_id) REFERENCES content_types(id)
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
        $initialItem->withSeoMetadata(
            'Hello Meta',
            'Meta description for hello world',
            '/images/hello.jpg',
            'https://example.com/hello-world',
            true,
            new DateTimeImmutable('2026-03-20 10:00:00')
        )
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
