<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentRelationship;
use App\Domain\Content\ContentType;
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

    public function findRelationshipRules(): array
    {
        $rows = $this->connection->fetchAll(
            'SELECT from_ct.slug AS from_type, to_ct.slug AS to_type, r.relation_type
             FROM content_type_relationship_rules r
             INNER JOIN content_types from_ct ON from_ct.id = r.from_content_type_id
             INNER JOIN content_types to_ct ON to_ct.id = r.to_content_type_id
             ORDER BY from_ct.slug ASC, to_ct.slug ASC, r.relation_type ASC'
        );

        return array_map(
            static fn (array $row): array => [
                'from_type' => (string) ($row['from_type'] ?? ''),
                'to_type' => (string) ($row['to_type'] ?? ''),
                'relation_type' => (string) ($row['relation_type'] ?? ''),
            ],
            $rows
        );
    }

    public function findRelationshipRulesForContentType(ContentType $type): array
    {
        $contentTypeId = $this->requireContentTypeId($type);
        $rows = $this->connection->fetchAll(
            'SELECT from_ct.slug AS from_type, to_ct.slug AS to_type, r.relation_type
             FROM content_type_relationship_rules r
             INNER JOIN content_types from_ct ON from_ct.id = r.from_content_type_id
             INNER JOIN content_types to_ct ON to_ct.id = r.to_content_type_id
             WHERE r.from_content_type_id = :content_type_id
                OR r.to_content_type_id = :content_type_id
             ORDER BY from_ct.slug ASC, to_ct.slug ASC, r.relation_type ASC',
            ['content_type_id' => $contentTypeId]
        );

        return array_map(
            static fn (array $row): array => [
                'from_type' => (string) ($row['from_type'] ?? ''),
                'to_type' => (string) ($row['to_type'] ?? ''),
                'relation_type' => (string) ($row['relation_type'] ?? ''),
            ],
            $rows
        );
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
        $this->assertRelationshipAllowedForItems($fromId, $toId, $normalizedType);

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

    public function allowRelationship(ContentType $from, ContentType $to, string $relationType): void
    {
        $fromTypeId = $this->requireContentTypeId($from);
        $toTypeId = $this->requireContentTypeId($to);
        $normalizedType = $this->normalizeRelationType($relationType);

        $existingRule = $this->connection->fetchOne(
            'SELECT 1
             FROM content_type_relationship_rules
             WHERE from_content_type_id = :from_content_type_id
               AND to_content_type_id = :to_content_type_id
               AND relation_type = :relation_type
             LIMIT 1',
            [
                'from_content_type_id' => $fromTypeId,
                'to_content_type_id' => $toTypeId,
                'relation_type' => $normalizedType,
            ]
        );

        if ($existingRule !== null) {
            return;
        }

        $this->connection->execute(
            'INSERT INTO content_type_relationship_rules
                (from_content_type_id, to_content_type_id, relation_type)
             VALUES
                (:from_content_type_id, :to_content_type_id, :relation_type)',
            [
                'from_content_type_id' => $fromTypeId,
                'to_content_type_id' => $toTypeId,
                'relation_type' => $normalizedType,
            ]
        );
    }

    public function isRelationshipAllowed(ContentType $from, ContentType $to, string $relationType): bool
    {
        $fromTypeId = $this->requireContentTypeId($from);
        $toTypeId = $this->requireContentTypeId($to);
        $normalizedType = $this->normalizeRelationType($relationType);

        $rule = $this->connection->fetchOne(
            'SELECT 1
             FROM content_type_relationship_rules
             WHERE from_content_type_id = :from_content_type_id
               AND to_content_type_id = :to_content_type_id
               AND relation_type = :relation_type
             LIMIT 1',
            [
                'from_content_type_id' => $fromTypeId,
                'to_content_type_id' => $toTypeId,
                'relation_type' => $normalizedType,
            ]
        );

        return $rule !== null;
    }

    public function removeRelationshipRule(ContentType $from, ContentType $to, string $relationType): void
    {
        $fromTypeId = $this->requireContentTypeId($from);
        $toTypeId = $this->requireContentTypeId($to);
        $normalizedType = $this->normalizeRelationType($relationType);

        $this->connection->execute(
            'DELETE FROM content_type_relationship_rules
             WHERE from_content_type_id = :from_content_type_id
               AND to_content_type_id = :to_content_type_id
               AND relation_type = :relation_type',
            [
                'from_content_type_id' => $fromTypeId,
                'to_content_type_id' => $toTypeId,
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

    private function assertRelationshipAllowedForItems(int $fromItemId, int $toItemId, string $relationType): void
    {
        $fromTypeId = $this->findContentTypeIdByItemId($fromItemId);
        $toTypeId = $this->findContentTypeIdByItemId($toItemId);

        $rule = $this->connection->fetchOne(
            'SELECT 1
             FROM content_type_relationship_rules
             WHERE from_content_type_id = :from_content_type_id
               AND to_content_type_id = :to_content_type_id
               AND relation_type = :relation_type
             LIMIT 1',
            [
                'from_content_type_id' => $fromTypeId,
                'to_content_type_id' => $toTypeId,
                'relation_type' => $relationType,
            ]
        );

        if ($rule === null) {
            throw new InvalidArgumentException(sprintf(
                'Relationship "%s" is not allowed from content type ID %d to content type ID %d.',
                $relationType,
                $fromTypeId,
                $toTypeId
            ));
        }
    }

    private function requireContentTypeId(ContentType $contentType): int
    {
        $row = $this->connection->fetchOne(
            'SELECT id FROM content_types WHERE slug = :slug LIMIT 1',
            ['slug' => $contentType->name()]
        );

        // fetchOne() returns an associative row (or null), not a scalar column value.
        if ($row === null) {
            throw new RuntimeException(sprintf('Content type "%s" does not exist in persistence.', $contentType->name()));
        }

        return (int) $row['id'];
    }

    private function findContentTypeIdByItemId(int $itemId): int
    {
        $row = $this->connection->fetchOne(
            'SELECT content_type_id
             FROM content_items
             WHERE id = :id
             LIMIT 1',
            ['id' => $itemId]
        );

        // fetchOne() returns an associative row (or null), not a scalar column value.
        if ($row === null) {
            throw new RuntimeException(sprintf('Content item %d does not exist in persistence.', $itemId));
        }

        return (int) $row['content_type_id'];
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
