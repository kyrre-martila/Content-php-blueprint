<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\ContentItem;
use App\Domain\Content\Category;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Slug;
use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use JsonException;
use RuntimeException;

final class MySqlContentItemRepository implements ContentItemRepositoryInterface
{
    private const FALLBACK_TEMPLATE = 'content/default.php';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(ContentItem $contentItem): ContentItem
    {
        if ($contentItem->id() === null) {
            return $this->create($contentItem);
        }

        return $this->update($contentItem);
    }

    public function findById(int $id): ?ContentItem
    {
        $row = $this->connection->fetchOne(
            $this->baseSelectSql() . ' WHERE ci.id = :id LIMIT 1',
            ['id' => $id]
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRowToContentItem($row);
    }

    public function findBySlug(Slug $slug): ?ContentItem
    {
        $row = $this->connection->fetchOne(
            $this->baseSelectSql() . ' WHERE ci.slug = :slug LIMIT 1',
            ['slug' => $slug->value()]
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRowToContentItem($row);
    }

    public function findChildrenOf(int $parentId): array
    {
        $rows = $this->connection->fetchAll(
            $this->baseSelectSql() . ' WHERE ci.parent_id = :parent_id ORDER BY ci.sort_order ASC, ci.id ASC',
            ['parent_id' => $parentId]
        );

        $contentItems = [];

        foreach ($rows as $row) {
            $contentItems[] = $this->mapRowToContentItem($row);
        }

        return $contentItems;
    }

    public function findRootItems(): array
    {
        $rows = $this->connection->fetchAll(
            $this->baseSelectSql() . ' WHERE ci.parent_id IS NULL ORDER BY ci.sort_order ASC, ci.id ASC'
        );

        $contentItems = [];

        foreach ($rows as $row) {
            $contentItems[] = $this->mapRowToContentItem($row);
        }

        return $contentItems;
    }

    public function findByType(
        ContentType $contentType,
        int $limit = ContentItemRepositoryInterface::DEFAULT_LIMIT,
        int $offset = ContentItemRepositoryInterface::DEFAULT_OFFSET
    ): array
    {
        ['limit' => $safeLimit, 'offset' => $safeOffset] = $this->normalizePagination($limit, $offset);

        $countRow = $this->connection->fetchOne(
            'SELECT COUNT(*) AS total_count
             FROM content_items ci
             INNER JOIN content_types ct ON ct.id = ci.content_type_id
             WHERE ct.slug = :slug',
            ['slug' => $contentType->name()]
        );

        $totalCount = $countRow === null ? 0 : $this->rowInt($countRow, 'total_count');

        $rows = $this->connection->fetchAll(
            $this->baseSelectSql() . ' WHERE ct.slug = :slug ORDER BY ci.created_at DESC, ci.id DESC LIMIT :limit OFFSET :offset',
            [
                'slug' => $contentType->name(),
                'limit' => $safeLimit,
                'offset' => $safeOffset,
            ]
        );

        $contentItems = [];

        foreach ($rows as $row) {
            $contentItems[] = $this->mapRowToContentItem($row);
        }

        return [
            'items' => $contentItems,
            'total_count' => $totalCount,
            'limit' => $safeLimit,
            'offset' => $safeOffset,
        ];
    }

    public function findAllWithTypes(
        int $limit = ContentItemRepositoryInterface::DEFAULT_LIMIT,
        int $offset = ContentItemRepositoryInterface::DEFAULT_OFFSET
    ): array
    {
        ['limit' => $safeLimit, 'offset' => $safeOffset] = $this->normalizePagination($limit, $offset);

        $countRow = $this->connection->fetchOne('SELECT COUNT(*) AS total_count FROM content_items');
        $totalCount = $countRow === null ? 0 : $this->rowInt($countRow, 'total_count');

        $rows = $this->connection->fetchAll(
            $this->baseSelectSql() . ' ORDER BY ct.slug ASC, ci.created_at DESC, ci.id DESC LIMIT :limit OFFSET :offset',
            [
                'limit' => $safeLimit,
                'offset' => $safeOffset,
            ]
        );

        /** @var array<string, list<ContentItem>> $groupedContentItems */
        $groupedContentItems = [];

        foreach ($rows as $row) {
            $typeSlug = $this->rowString($row, 'type_slug');

            if (!array_key_exists($typeSlug, $groupedContentItems)) {
                $groupedContentItems[$typeSlug] = [];
            }

            $groupedContentItems[$typeSlug][] = $this->mapRowToContentItem($row);
        }

        return [
            'items' => $groupedContentItems,
            'total_count' => $totalCount,
            'limit' => $safeLimit,
            'offset' => $safeOffset,
        ];
    }

    public function findPublished(
        int $limit = ContentItemRepositoryInterface::DEFAULT_LIMIT,
        int $offset = ContentItemRepositoryInterface::DEFAULT_OFFSET
    ): array
    {
        ['limit' => $safeLimit, 'offset' => $safeOffset] = $this->normalizePagination($limit, $offset);

        $countRow = $this->connection->fetchOne(
            'SELECT COUNT(*) AS total_count FROM content_items ci WHERE ci.status = :status',
            ['status' => ContentStatus::Published->value]
        );

        $totalCount = $countRow === null ? 0 : $this->rowInt($countRow, 'total_count');

        $rows = $this->connection->fetchAll(
            $this->baseSelectSql() . ' WHERE ci.status = :status ORDER BY ci.updated_at DESC, ci.id DESC LIMIT :limit OFFSET :offset',
            [
                'status' => ContentStatus::Published->value,
                'limit' => $safeLimit,
                'offset' => $safeOffset,
            ]
        );

        $contentItems = [];

        foreach ($rows as $row) {
            $contentItems[] = $this->mapRowToContentItem($row);
        }

        return [
            'items' => $contentItems,
            'total_count' => $totalCount,
            'limit' => $safeLimit,
            'offset' => $safeOffset,
        ];
    }

    public function findPublishedByType(
        ContentType $contentType,
        int $limit = ContentItemRepositoryInterface::DEFAULT_LIMIT,
        int $offset = ContentItemRepositoryInterface::DEFAULT_OFFSET
    ): array
    {
        ['limit' => $safeLimit, 'offset' => $safeOffset] = $this->normalizePagination($limit, $offset);

        $countRow = $this->connection->fetchOne(
            'SELECT COUNT(*) AS total_count
             FROM content_items ci
             INNER JOIN content_types ct ON ct.id = ci.content_type_id
             WHERE ct.slug = :slug
               AND ci.status = :status',
            [
                'slug' => $contentType->name(),
                'status' => ContentStatus::Published->value,
            ]
        );

        $totalCount = $countRow === null ? 0 : $this->rowInt($countRow, 'total_count');

        $rows = $this->connection->fetchAll(
            $this->baseSelectSql() . ' WHERE ct.slug = :slug AND ci.status = :status ORDER BY ci.updated_at DESC, ci.id DESC LIMIT :limit OFFSET :offset',
            [
                'slug' => $contentType->name(),
                'status' => ContentStatus::Published->value,
                'limit' => $safeLimit,
                'offset' => $safeOffset,
            ]
        );

        $contentItems = [];

        foreach ($rows as $row) {
            $contentItems[] = $this->mapRowToContentItem($row);
        }

        return [
            'items' => $contentItems,
            'total_count' => $totalCount,
            'limit' => $safeLimit,
            'offset' => $safeOffset,
        ];
    }

    public function findPublishedByCategory(
        Category $category,
        int $limit = ContentItemRepositoryInterface::DEFAULT_LIMIT,
        int $offset = ContentItemRepositoryInterface::DEFAULT_OFFSET
    ): array
    {
        ['limit' => $safeLimit, 'offset' => $safeOffset] = $this->normalizePagination($limit, $offset);

        $categoryId = $category->id();

        if ($categoryId === null) {
            return [
                'items' => [],
                'total_count' => 0,
                'limit' => $safeLimit,
                'offset' => $safeOffset,
            ];
        }

        $countRow = $this->connection->fetchOne(
            'SELECT COUNT(*) AS total_count
             FROM content_items ci
             INNER JOIN content_item_categories cic ON cic.content_item_id = ci.id
             WHERE cic.category_id = :category_id
               AND ci.status = :status',
            [
                'category_id' => $categoryId,
                'status' => ContentStatus::Published->value,
            ]
        );

        $totalCount = $countRow === null ? 0 : $this->rowInt($countRow, 'total_count');

        $rows = $this->connection->fetchAll(
            $this->baseSelectSql() . ' INNER JOIN content_item_categories cic ON cic.content_item_id = ci.id
             WHERE cic.category_id = :category_id
               AND ci.status = :status
             ORDER BY ci.updated_at DESC, ci.id DESC
             LIMIT :limit OFFSET :offset',
            [
                'category_id' => $categoryId,
                'status' => ContentStatus::Published->value,
                'limit' => $safeLimit,
                'offset' => $safeOffset,
            ]
        );

        $contentItems = [];

        foreach ($rows as $row) {
            $contentItems[] = $this->mapRowToContentItem($row);
        }

        return [
            'items' => $contentItems,
            'total_count' => $totalCount,
            'limit' => $safeLimit,
            'offset' => $safeOffset,
        ];
    }

    public function remove(ContentItem $contentItem): void
    {
        if ($contentItem->id() === null) {
            throw new RuntimeException('Cannot remove content item without an ID.');
        }

        $affectedRows = $this->connection->execute(
            'DELETE FROM content_items WHERE id = :id',
            ['id' => $contentItem->id()]
        );

        if ($affectedRows < 1) {
            throw new RuntimeException(sprintf('Content item with ID %d was not found for removal.', $contentItem->id()));
        }
    }

    private function create(ContentItem $contentItem): ContentItem
    {
        $contentTypeId = $this->findContentTypeIdByMachineName($contentItem->type()->name());

        $insertedId = $this->connection->insertAndGetId(
            'INSERT INTO content_items (
                content_type_id,
                title,
                slug,
                status,
                pattern_blocks,
                meta_title,
                meta_description,
                og_image,
                canonical_url,
                noindex,
                parent_id,
                sort_order,
                created_at,
                updated_at
            )
             VALUES (
                :content_type_id,
                :title,
                :slug,
                :status,
                :pattern_blocks,
                :meta_title,
                :meta_description,
                :og_image,
                :canonical_url,
                :noindex,
                :parent_id,
                :sort_order,
                :created_at,
                :updated_at
            )',
            [
                'content_type_id' => $contentTypeId,
                'title' => $contentItem->title(),
                'slug' => $contentItem->slug()->value(),
                'status' => $contentItem->status()->value,
                'pattern_blocks' => $this->encodePatternBlocks($contentItem->patternBlocks()),
                'meta_title' => $contentItem->metaTitle(),
                'meta_description' => $contentItem->metaDescription(),
                'og_image' => $contentItem->ogImage(),
                'canonical_url' => $contentItem->canonicalUrl(),
                'noindex' => $contentItem->noindex() ? 1 : 0,
                'parent_id' => $contentItem->parentId(),
                'sort_order' => $contentItem->sortOrder(),
                'created_at' => $contentItem->createdAt()->format('Y-m-d H:i:s'),
                'updated_at' => $contentItem->updatedAt()->format('Y-m-d H:i:s'),
            ]
        );

        if (!is_numeric($insertedId)) {
            throw new RuntimeException('Failed to create content item record.');
        }

        $createdContentItem = $this->findById((int) $insertedId);

        if ($createdContentItem === null) {
            throw new RuntimeException('Content item was created but could not be reloaded.');
        }

        return $createdContentItem;
    }

    private function update(ContentItem $contentItem): ContentItem
    {
        $contentTypeId = $this->findContentTypeIdByMachineName($contentItem->type()->name());

        $id = $contentItem->id();

        if ($id === null) {
            throw new RuntimeException('Cannot update content item without an ID.');
        }

        $affectedRows = $this->connection->execute(
            'UPDATE content_items
             SET content_type_id = :content_type_id,
                 title = :title,
                 slug = :slug,
                 status = :status,
                 pattern_blocks = :pattern_blocks,
                 meta_title = :meta_title,
                 meta_description = :meta_description,
                 og_image = :og_image,
                 canonical_url = :canonical_url,
                 noindex = :noindex,
                 parent_id = :parent_id,
                 sort_order = :sort_order,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'id' => $id,
                'content_type_id' => $contentTypeId,
                'title' => $contentItem->title(),
                'slug' => $contentItem->slug()->value(),
                'status' => $contentItem->status()->value,
                'pattern_blocks' => $this->encodePatternBlocks($contentItem->patternBlocks()),
                'meta_title' => $contentItem->metaTitle(),
                'meta_description' => $contentItem->metaDescription(),
                'og_image' => $contentItem->ogImage(),
                'canonical_url' => $contentItem->canonicalUrl(),
                'noindex' => $contentItem->noindex() ? 1 : 0,
                'parent_id' => $contentItem->parentId(),
                'sort_order' => $contentItem->sortOrder(),
                'updated_at' => $contentItem->updatedAt()->format('Y-m-d H:i:s'),
            ]
        );

        if ($affectedRows < 1) {
            throw new RuntimeException(sprintf('Content item with ID %d was not found for update.', $id));
        }

        $updatedContentItem = $this->findById($id);

        if ($updatedContentItem === null) {
            throw new RuntimeException('Content item was updated but could not be reloaded.');
        }

        return $updatedContentItem;
    }

    private function findContentTypeIdByMachineName(string $machineName): int
    {
        $row = $this->connection->fetchOne(
            'SELECT id FROM content_types WHERE slug = :slug LIMIT 1',
            ['slug' => $machineName]
        );

        if ($row === null) {
            throw new RuntimeException(sprintf('Content type "%s" does not exist.', $machineName));
        }

        return $this->rowInt($row, 'id');
    }

    private function baseSelectSql(): string
    {
        return 'SELECT
                    ci.id,
                    ci.title,
                    ci.slug,
                    ci.status,
                    ci.pattern_blocks,
                    ci.meta_title,
                    ci.meta_description,
                    ci.og_image,
                    ci.canonical_url,
                    ci.noindex,
                    ci.parent_id,
                    ci.sort_order,
                    ci.created_at,
                    ci.updated_at,
                    ct.slug AS type_slug,
                    ct.name AS type_name,
                    ct.description AS type_template,
                    ct.view_type AS type_view_type
                FROM content_items ci
                INNER JOIN content_types ct ON ct.id = ci.content_type_id';
    }

    /**
     * @return array{limit: int, offset: int}
     */
    private function normalizePagination(int $limit, int $offset): array
    {
        $safeLimit = $limit > 0 ? $limit : ContentItemRepositoryInterface::DEFAULT_LIMIT;
        $safeOffset = max(0, $offset);

        return [
            'limit' => $safeLimit,
            'offset' => $safeOffset,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToContentItem(array $row): ContentItem
    {
        $template = $row['type_template'] ?? null;
        $defaultTemplate = is_string($template) && trim($template) !== '' ? $template : self::FALLBACK_TEMPLATE;

        $contentType = new ContentType(
            $this->rowString($row, 'type_slug'),
            $this->rowString($row, 'type_name'),
            $defaultTemplate,
            [],
            ContentViewType::fromString($this->rowString($row, 'type_view_type'))
        );

        return new ContentItem(
            $this->rowInt($row, 'id'),
            $contentType,
            $this->rowString($row, 'title'),
            Slug::fromString($this->rowString($row, 'slug')),
            ContentStatus::fromString($this->rowString($row, 'status')),
            new DateTimeImmutable($this->rowString($row, 'created_at')),
            new DateTimeImmutable($this->rowString($row, 'updated_at')),
            $this->decodePatternBlocks($row['pattern_blocks'] ?? null),
            $this->nullableString($row, 'meta_title'),
            $this->nullableString($row, 'meta_description'),
            $this->nullableString($row, 'og_image'),
            $this->nullableString($row, 'canonical_url'),
            $this->rowBool($row, 'noindex'),
            $this->nullableInt($row, 'parent_id'),
            $this->rowInt($row, 'sort_order')
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowString(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        if (!is_scalar($value)) {
            throw new RuntimeException(sprintf('Column "%s" is missing from content_items query result.', $key));
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowInt(array $row, string $key): int
    {
        $value = $row[$key] ?? null;

        if (!is_scalar($value) || !is_numeric((string) $value)) {
            throw new RuntimeException(sprintf('Column "%s" is not a valid integer.', $key));
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function nullableString(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (!is_scalar($value)) {
            throw new RuntimeException(sprintf('Column "%s" is not a valid string.', $key));
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function nullableInt(array $row, string $key): ?int
    {
        $value = $row[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (!is_scalar($value) || !is_numeric((string) $value)) {
            throw new RuntimeException(sprintf('Column "%s" is not a valid nullable integer.', $key));
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowBool(array $row, string $key): bool
    {
        $value = $row[$key] ?? null;

        if (is_bool($value)) {
            return $value;
        }

        if (!is_scalar($value)) {
            throw new RuntimeException(sprintf('Column "%s" is not a valid boolean.', $key));
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param list<array{pattern: string, data: array<string, string>}> $blocks
     */
    private function encodePatternBlocks(array $blocks): string
    {
        try {
            return json_encode($blocks, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to encode content item pattern blocks as JSON.', 0, $exception);
        }
    }

    /**
     * @return list<array{pattern: string, data: array<string, string>}>
     */
    private function decodePatternBlocks(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Stored pattern blocks JSON is invalid.', 0, $exception);
        }

        if (!is_array($decoded)) {
            return [];
        }

        $validated = [];

        foreach ($decoded as $block) {
            if (!is_array($block)) {
                continue;
            }

            $pattern = $block['pattern'] ?? null;
            $data = $block['data'] ?? null;

            if (!is_string($pattern) || trim($pattern) === '' || !is_array($data)) {
                continue;
            }

            $normalizedData = [];

            foreach ($data as $key => $fieldValue) {
                if (!is_string($key) || !is_scalar($fieldValue)) {
                    continue;
                }

                $normalizedData[$key] = (string) $fieldValue;
            }

            $validated[] = [
                'pattern' => trim($pattern),
                'data' => $normalizedData,
            ];
        }

        return $validated;
    }
}
