<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\CategoryGroup;
use App\Domain\Content\Repository\CategoryGroupRepositoryInterface;
use App\Domain\Content\Slug;
use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use RuntimeException;

final class MySqlCategoryGroupRepository implements CategoryGroupRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(CategoryGroup $group): CategoryGroup
    {
        if ($group->id() === null) {
            return $this->create($group);
        }

        return $this->update($group);
    }

    public function findById(int $id): ?CategoryGroup
    {
        $row = $this->connection->fetchOne(
            'SELECT id, name, slug, description, created_at, updated_at
             FROM category_groups
             WHERE id = :id
             LIMIT 1',
            ['id' => $id]
        );

        return $row === null ? null : $this->mapRowToCategoryGroup($row);
    }

    public function findBySlug(string $slug): ?CategoryGroup
    {
        $row = $this->connection->fetchOne(
            'SELECT id, name, slug, description, created_at, updated_at
             FROM category_groups
             WHERE slug = :slug
             LIMIT 1',
            ['slug' => $slug]
        );

        return $row === null ? null : $this->mapRowToCategoryGroup($row);
    }

    public function findAllGroups(): array
    {
        $rows = $this->connection->fetchAll(
            'SELECT id, name, slug, description, created_at, updated_at
             FROM category_groups
             ORDER BY name ASC, id ASC'
        );

        return array_map(fn (array $row): CategoryGroup => $this->mapRowToCategoryGroup($row), $rows);
    }

    private function create(CategoryGroup $group): CategoryGroup
    {
        $id = $this->connection->insertAndGetId(
            'INSERT INTO category_groups (name, slug, description, created_at, updated_at)
             VALUES (:name, :slug, :description, :created_at, :updated_at)',
            [
                'name' => $group->name(),
                'slug' => $group->slug()->value(),
                'description' => $group->description(),
                'created_at' => $group->createdAt()->format('Y-m-d H:i:s'),
                'updated_at' => $group->updatedAt()->format('Y-m-d H:i:s'),
            ]
        );

        return $this->findById((int) $id) ?? throw new RuntimeException('Unable to load created category group.');
    }

    private function update(CategoryGroup $group): CategoryGroup
    {
        $id = $group->id();

        if ($id === null) {
            throw new RuntimeException('Cannot update category group without ID.');
        }

        $this->connection->execute(
            'UPDATE category_groups
             SET name = :name,
                 slug = :slug,
                 description = :description,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'id' => $id,
                'name' => $group->name(),
                'slug' => $group->slug()->value(),
                'description' => $group->description(),
                'updated_at' => $group->updatedAt()->format('Y-m-d H:i:s'),
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Unable to load updated category group.');
    }

    /** @param array<string, mixed> $row */
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
}
