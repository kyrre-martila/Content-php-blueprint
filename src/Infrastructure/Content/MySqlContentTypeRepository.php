<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\CategoryGroup;
use App\Domain\Content\ContentType;
use App\Domain\Content\ContentTypeField;
use App\Domain\Content\ContentViewType;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Domain\Content\Slug;
use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use RuntimeException;

final class MySqlContentTypeRepository implements ContentTypeRepositoryInterface
{
    private const FALLBACK_TEMPLATE = 'content/default.php';

    private readonly MySqlContentTypeFieldRepository $fieldRepository;

    public function __construct(private readonly Connection $connection, ?MySqlContentTypeFieldRepository $fieldRepository = null)
    {
        $this->fieldRepository = $fieldRepository ?? new MySqlContentTypeFieldRepository($connection);
    }

    public function save(ContentType $contentType): ContentType
    {
        $existingRow = $this->connection->fetchOne(
            'SELECT id FROM content_types WHERE slug = :slug LIMIT 1',
            ['slug' => $contentType->name()]
        );

        if ($existingRow === null) {
            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

            $insertedId = (int) $this->connection->insertAndGetId(
                'INSERT INTO content_types (name, slug, description, view_type, created_at, updated_at)
                 VALUES (:name, :slug, :description, :view_type, :created_at, :updated_at)',
                [
                    'name' => $contentType->label(),
                    'slug' => $contentType->name(),
                    'description' => $contentType->defaultTemplate(),
                    'view_type' => $contentType->viewType()->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $this->fieldRepository->replaceForContentType($insertedId, $this->rebindFieldsToContentType($contentType->fields(), $insertedId));

            return $contentType;
        }

        $id = $this->rowInt($existingRow, 'id');

        $affectedRows = $this->connection->execute(
            'UPDATE content_types
             SET name = :name,
                 description = :description,
                 view_type = :view_type,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'id' => $id,
                'name' => $contentType->label(),
                'description' => $contentType->defaultTemplate(),
                'view_type' => $contentType->viewType()->value,
                'updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );

        $this->fieldRepository->replaceForContentType($id, $this->rebindFieldsToContentType($contentType->fields(), $id));

        if ($affectedRows < 1) {
            throw new RuntimeException('Failed to update content type record.');
        }

        return $contentType;
    }

    public function findByName(string $name): ?ContentType
    {
        $row = $this->connection->fetchOne(
            'SELECT id, name, slug, description, view_type
             FROM content_types
             WHERE slug = :slug
             LIMIT 1',
            ['slug' => $name]
        );

        if ($row === null) {
            return null;
        }

        $contentTypeId = $this->rowInt($row, 'id');

        return $this->mapRowToContentType(
            $row,
            $this->fieldRepository->findByContentTypeId($contentTypeId),
            $this->loadAllowedCategoryGroupIds($contentTypeId)
        );
    }

    public function findAll(): array
    {
        $rows = $this->connection->fetchAll(
            'SELECT id, name, slug, description, view_type
             FROM content_types
             ORDER BY name ASC'
        );

        $contentTypes = [];

        foreach ($rows as $row) {
            $contentTypeId = $this->rowInt($row, 'id');
            $contentTypes[] = $this->mapRowToContentType(
                $row,
                $this->fieldRepository->findByContentTypeId($contentTypeId),
                $this->loadAllowedCategoryGroupIds($contentTypeId)
            );
        }

        return $contentTypes;
    }

    public function getAllowedCategoryGroups(ContentType $type): array
    {
        $contentTypeId = $this->findContentTypeIdBySlug($type->name());

        $rows = $this->connection->fetchAll(
            'SELECT cg.id, cg.name, cg.slug, cg.description, cg.created_at, cg.updated_at
             FROM category_groups cg
             INNER JOIN content_type_category_groups ctcg ON ctcg.category_group_id = cg.id
             WHERE ctcg.content_type_id = :content_type_id
             ORDER BY cg.name ASC, cg.id ASC',
            ['content_type_id' => $contentTypeId]
        );

        return array_map(fn (array $row): CategoryGroup => $this->mapRowToCategoryGroup($row), $rows);
    }

    public function attachCategoryGroup(ContentType $type, CategoryGroup $group): void
    {
        $groupId = $group->id();

        if ($groupId === null) {
            throw new RuntimeException('Cannot attach a category group without an ID.');
        }

        $contentTypeId = $this->findContentTypeIdBySlug($type->name());

        $existing = $this->connection->fetchOne(
            'SELECT content_type_id
             FROM content_type_category_groups
             WHERE content_type_id = :content_type_id
               AND category_group_id = :category_group_id
             LIMIT 1',
            [
                'content_type_id' => $contentTypeId,
                'category_group_id' => $groupId,
            ]
        );

        if ($existing !== null) {
            return;
        }

        $this->connection->execute(
            'INSERT INTO content_type_category_groups (content_type_id, category_group_id)
             VALUES (:content_type_id, :category_group_id)',
            [
                'content_type_id' => $contentTypeId,
                'category_group_id' => $groupId,
            ]
        );
    }

    public function detachCategoryGroup(ContentType $type, CategoryGroup $group): void
    {
        $groupId = $group->id();

        if ($groupId === null) {
            return;
        }

        $contentTypeId = $this->findContentTypeIdBySlug($type->name());

        $this->connection->execute(
            'DELETE FROM content_type_category_groups
             WHERE content_type_id = :content_type_id
               AND category_group_id = :category_group_id',
            [
                'content_type_id' => $contentTypeId,
                'category_group_id' => $groupId,
            ]
        );
    }

    public function remove(ContentType $contentType): void
    {
        $affectedRows = $this->connection->execute(
            'DELETE FROM content_types WHERE slug = :slug',
            ['slug' => $contentType->name()]
        );

        if ($affectedRows < 1) {
            throw new RuntimeException(sprintf('Content type "%s" was not found for removal.', $contentType->name()));
        }
    }

    /**
     * @param list<ContentTypeField> $fields
     * @param list<int> $allowedCategoryGroupIds
     */
    private function mapRowToContentType(array $row, array $fields = [], array $allowedCategoryGroupIds = []): ContentType
    {
        $machineName = $this->rowString($row, 'slug');
        $label = $this->rowString($row, 'name');

        $template = $row['description'] ?? null;
        $defaultTemplate = is_string($template) && trim($template) !== '' ? $template : self::FALLBACK_TEMPLATE;

        $viewType = $this->rowString($row, 'view_type');

        return new ContentType(
            $machineName,
            $label,
            $defaultTemplate,
            $fields,
            ContentViewType::fromString($viewType),
            $allowedCategoryGroupIds
        );
    }

    /**
     * @return list<int>
     */
    private function loadAllowedCategoryGroupIds(int $contentTypeId): array
    {
        $rows = $this->connection->fetchAll(
            'SELECT category_group_id
             FROM content_type_category_groups
             WHERE content_type_id = :content_type_id
             ORDER BY category_group_id ASC',
            ['content_type_id' => $contentTypeId]
        );

        return array_map(
            fn (array $row): int => $this->rowInt($row, 'category_group_id'),
            $rows
        );
    }


    /**
     * @param list<ContentTypeField> $fields
     * @return list<ContentTypeField>
     */
    private function rebindFieldsToContentType(array $fields, int $contentTypeId): array
    {
        $now = new DateTimeImmutable();

        return array_map(
            static fn (ContentTypeField $field): ContentTypeField => new ContentTypeField(
                id: null,
                contentTypeId: $contentTypeId,
                name: $field->name(),
                label: $field->label(),
                fieldType: $field->fieldType(),
                isRequired: $field->isRequired(),
                defaultValue: $field->defaultValue(),
                settings: $field->settings(),
                sortOrder: $field->sortOrder(),
                createdAt: $now,
                updatedAt: $now
            ),
            $fields
        );
    }

    private function findContentTypeIdBySlug(string $slug): int
    {
        $row = $this->connection->fetchOne(
            'SELECT id FROM content_types WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        );

        if ($row === null) {
            throw new RuntimeException(sprintf('Content type "%s" was not found.', $slug));
        }

        return $this->rowInt($row, 'id');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToCategoryGroup(array $row): CategoryGroup
    {
        return new CategoryGroup(
            id: isset($row['id']) ? (int) $row['id'] : null,
            name: (string) ($row['name'] ?? ''),
            slug: Slug::fromString((string) ($row['slug'] ?? '')),
            description: isset($row['description']) ? (string) $row['description'] : null,
            createdAt: new DateTimeImmutable((string) ($row['created_at'] ?? 'now')),
            updatedAt: new DateTimeImmutable((string) ($row['updated_at'] ?? 'now'))
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowString(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        if (!is_scalar($value)) {
            throw new RuntimeException(sprintf('Column "%s" is missing from content_types query result.', $key));
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
