<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Slug;
use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
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

    public function findByType(ContentType $contentType): array
    {
        $rows = $this->connection->fetchAll(
            $this->baseSelectSql() . ' WHERE ct.slug = :slug ORDER BY ci.created_at DESC, ci.id DESC',
            ['slug' => $contentType->name()]
        );

        $contentItems = [];

        foreach ($rows as $row) {
            $contentItems[] = $this->mapRowToContentItem($row);
        }

        return $contentItems;
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
            'INSERT INTO content_items (content_type_id, title, slug, status, created_at, updated_at)
             VALUES (:content_type_id, :title, :slug, :status, :created_at, :updated_at)',
            [
                'content_type_id' => $contentTypeId,
                'title' => $contentItem->title(),
                'slug' => $contentItem->slug()->value(),
                'status' => $contentItem->status()->value,
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
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'id' => $id,
                'content_type_id' => $contentTypeId,
                'title' => $contentItem->title(),
                'slug' => $contentItem->slug()->value(),
                'status' => $contentItem->status()->value,
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
                    ci.created_at,
                    ci.updated_at,
                    ct.slug AS type_slug,
                    ct.name AS type_name,
                    ct.description AS type_template
                FROM content_items ci
                INNER JOIN content_types ct ON ct.id = ci.content_type_id';
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
            $defaultTemplate
        );

        return new ContentItem(
            $this->rowInt($row, 'id'),
            $contentType,
            $this->rowString($row, 'title'),
            Slug::fromString($this->rowString($row, 'slug')),
            ContentStatus::fromString($this->rowString($row, 'status')),
            new DateTimeImmutable($this->rowString($row, 'created_at')),
            new DateTimeImmutable($this->rowString($row, 'updated_at'))
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
}
