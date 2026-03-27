<?php

declare(strict_types=1);

use App\Application\OCF\OCFExporter;
use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentType;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Domain\Content\Slug;

it('builds a content-only OCF payload without presentation concerns', function (): void {
    $pageType = new ContentType('page', 'Page', 'content/default.php');

    $item = ContentItem::draft(
        id: 1,
        type: $pageType,
        title: 'About us',
        slug: Slug::fromString('about'),
        createdAt: new DateTimeImmutable('2026-03-27T10:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2026-03-27T11:00:00+00:00')
    )->withPatternBlocks([
        ['pattern' => 'hero', 'data' => ['headline' => 'About us']],
    ], new DateTimeImmutable('2026-03-27T11:00:00+00:00'));

    $exporter = new OCFExporter(
        new class([$pageType]) implements ContentTypeRepositoryInterface {
            /**
             * @param list<ContentType> $types
             */
            public function __construct(private readonly array $types)
            {
            }

            public function save(ContentType $contentType): ContentType
            {
                return $contentType;
            }

            public function findByName(string $name): ?ContentType
            {
                return null;
            }

            public function findAll(): array
            {
                return $this->types;
            }

            public function remove(ContentType $contentType): void
            {
            }
        },
        new class([$item]) implements ContentItemRepositoryInterface {
            /**
             * @param list<ContentItem> $items
             */
            public function __construct(private readonly array $items)
            {
            }

            public function save(ContentItem $contentItem): ContentItem
            {
                return $contentItem;
            }

            public function findById(int $id): ?ContentItem
            {
                return null;
            }

            public function findBySlug(Slug $slug): ?ContentItem
            {
                return null;
            }

            public function findByType(ContentType $contentType): array
            {
                return array_values(array_filter(
                    $this->items,
                    static fn (ContentItem $item): bool => $item->type()->equals($contentType)
                ));
            }

            public function remove(ContentItem $contentItem): void
            {
            }
        },
        sys_get_temp_dir()
    );

    $payload = $exporter->buildPayload();

    expect($payload['content_types'])->toHaveCount(1)
        ->and($payload['content_types'][0]['name'])->toBe('page')
        ->and($payload['content_items'])->toHaveCount(1)
        ->and($payload['content_items'][0]['slug'])->toBe('about')
        ->and($payload['content_items'][0]['fields']['title'])->toBe('About us')
        ->and($payload['content_items'][0])->not->toHaveKey('pattern_blocks')
        ->and($payload['content_items'][0])->not->toHaveKey('template')
        ->and($payload['content_items'][0])->not->toHaveKey('layout');
});

it('writes content-export.json and creates storage/exports/ocf automatically', function (): void {
    $pageType = new ContentType('page', 'Page', 'content/default.php');
    $item = ContentItem::draft(
        id: 9,
        type: $pageType,
        title: 'Contact',
        slug: Slug::fromString('contact'),
        createdAt: new DateTimeImmutable('2026-03-27T10:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2026-03-27T11:00:00+00:00')
    );

    $projectRoot = sys_get_temp_dir() . '/ocf-exporter-' . uniqid('', true);

    $exporter = new OCFExporter(
        new class($pageType) implements ContentTypeRepositoryInterface {
            public function __construct(private readonly ContentType $type)
            {
            }

            public function save(ContentType $contentType): ContentType
            {
                return $contentType;
            }

            public function findByName(string $name): ?ContentType
            {
                return $this->type->name() === $name ? $this->type : null;
            }

            public function findAll(): array
            {
                return [$this->type];
            }

            public function remove(ContentType $contentType): void
            {
            }
        },
        new class($item) implements ContentItemRepositoryInterface {
            public function __construct(private readonly ContentItem $item)
            {
            }

            public function save(ContentItem $contentItem): ContentItem
            {
                return $contentItem;
            }

            public function findById(int $id): ?ContentItem
            {
                return null;
            }

            public function findBySlug(Slug $slug): ?ContentItem
            {
                return null;
            }

            public function findByType(ContentType $contentType): array
            {
                return [$this->item];
            }

            public function remove(ContentItem $contentItem): void
            {
            }
        },
        $projectRoot
    );

    $exportPath = $exporter->exportAll();

    expect($exportPath)->toBe($projectRoot . '/storage/exports/ocf/content-export.json')
        ->and(is_file($exportPath))->toBeTrue();

    $decoded = json_decode((string) file_get_contents($exportPath), true, 512, JSON_THROW_ON_ERROR);

    expect($decoded['content_items'][0]['slug'])->toBe('contact');
});
