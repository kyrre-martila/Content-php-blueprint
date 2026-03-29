<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentRelationship;
use App\Domain\Content\Repository\ContentRelationshipRepositoryInterface;
use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class MySqlContentRelationshipRepository implements ContentRelationshipRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function findOutgoingRelationships(ContentItem $item): array
    {
        $itemId = $this->requireItemId($item, 'source');

        $rows = $this->connection->fetchAll(
            'SELECT id, from_content_item_id, to_content_item_id, relation_type, sort_order, created_at, updated_at
             FROM content_item_relationships
             WHERE from_content_item_id = :item_id
             ORDER BY relation_type ASC, sort_order ASC, id ASC',
            ['item_id' => $itemId]
        );

        return array_map($this->mapRowToRelationship(...), $rows);
    }

    public function findIncomingRelationships(ContentItem $item): array
    {
        $itemId = $this->requireItemId($item, 'target');

        $rows = $this->connection->fetchAll(
            'SELECT id, from_content_item_id, to_content_item_id, relation_type, sort_order, created_at, updated_at
             FROM content_item_relationships
             WHERE to_content_item_id = :item_id
             ORDER BY relation_type ASC, sort_order ASC, id ASC',
            ['item_id' => $itemId]
        );

        return array_map($this->mapRowToRelationship(...), $rows);
    }

    public function findByType(ContentItem $item, string $relationType): array
    {
        $itemId = $this->requireItemId($item, 'source');
        $normalizedType = $this->normalizeRelationType($relationType);

        $rows = $this->connection->fetchAll(
            'SELECT id, from_content_item_id, to_content_item_id, relation_type, sort_order, created_at, updated_at
             FROM content_item_relationships
             WHERE from_content_item_id = :item_id
               AND relation_type = :relation_type
             ORDER BY sort_order ASC, id ASC',
            [
                'item_id' => $itemId,
                'relation_type' => $normalizedType,
            ]
        );

        return array_map($this->mapRowToRelationship(...), $rows);
    }

    public function attach(ContentItem $from, ContentItem $to, string $relationType, int $sortOrder = 0): void
    {
        $fromId = $this->requireItemId($from, 'source');
        $toId = $this->requireItemId($to, 'target');

        if ($fromId === $toId) {
            throw new InvalidArgumentException('Self-referencing relationships are not currently allowed.');
        }

        $normalizedType = $this->normalizeRelationType($relationType);

        $existing = $this->connection->fetchOne(
            'SELECT id FROM content_item_relationships
             WHERE from_content_item_id = :from_id
               AND to_content_item_id = :to_id
               AND relation_type = :relation_type
             LIMIT 1',
            [
                'from_id' => $fromId,
                'to_id' => $toId,
                'relation_type' => $normalizedType,
            ]
        );

        if ($existing !== null) {
            return;
        }

        $this->connection->execute(
            'INSERT INTO content_item_relationships
                (from_content_item_id, to_content_item_id, relation_type, sort_order)
             VALUES
                (:from_id, :to_id, :relation_type, :sort_order)',
            [
                'from_id' => $fromId,
                'to_id' => $toId,
                'relation_type' => $normalizedType,
                'sort_order' => $sortOrder,
            ]
        );
    }

    public function detach(ContentItem $from, ContentItem $to, string $relationType): void
    {
        $fromId = $this->requireItemId($from, 'source');
        $toId = $this->requireItemId($to, 'target');
        $normalizedType = $this->normalizeRelationType($relationType);

        $this->connection->execute(
            'DELETE FROM content_item_relationships
             WHERE from_content_item_id = :from_id
               AND to_content_item_id = :to_id
               AND relation_type = :relation_type',
            [
                'from_id' => $fromId,
                'to_id' => $toId,
                'relation_type' => $normalizedType,
            ]
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToRelationship(array $row): ContentRelationship
    {
        return new ContentRelationship(
            id: $this->rowInt($row, 'id'),
            fromContentItemId: $this->rowInt($row, 'from_content_item_id'),
            toContentItemId: $this->rowInt($row, 'to_content_item_id'),
            relationType: $this->rowString($row, 'relation_type'),
            sortOrder: $this->rowInt($row, 'sort_order'),
            createdAt: new DateTimeImmutable($this->rowString($row, 'created_at')),
            updatedAt: new DateTimeImmutable($this->rowString($row, 'updated_at'))
        );
    }

    private function requireItemId(ContentItem $item, string $position): int
    {
        $itemId = $item->id();

        if ($itemId === null) {
            throw new RuntimeException(sprintf('Cannot use an unsaved %s content item for relationships.', $position));
        }

        return $itemId;
    }

    private function normalizeRelationType(string $relationType): string
    {
        $normalizedType = trim($relationType);

        if ($normalizedType === '') {
            throw new InvalidArgumentException('Relationship type cannot be empty.');
        }

        return $normalizedType;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowString(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        if (!is_string($value) || $value === '') {
            throw new RuntimeException(sprintf('Expected non-empty string for key "%s".', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowInt(array $row, string $key): int
    {
        $value = $row[$key] ?? null;

        if (!is_int($value) && !is_string($value)) {
            throw new RuntimeException(sprintf('Expected integer-compatible value for key "%s".', $key));
        }

        return (int) $value;
    }
}
