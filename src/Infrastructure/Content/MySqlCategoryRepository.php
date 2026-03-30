<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\Category;
use App\Domain\Content\CategoryGroup;
use App\Domain\Content\ContentItem;
use App\Domain\Content\Repository\CategoryRepositoryInterface;
use App\Domain\Content\Slug;
use App\Infrastructure\Database\Connection;
use DateTimeImmutable;
use RuntimeException;

final class MySqlCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(Category $category): Category
    {
        if ($category->id() === null) {
            return $this->create($category);
        }

        return $this->update($category);
    }

    public function findById(int $id): ?Category
    {
        $row = $this->connection->fetchOne(
            'SELECT id, group_id, parent_id, name, slug, description, sort_order, created_at, updated_at
             FROM categories
             WHERE id = :id
             LIMIT 1',
            ['id' => $id]
        );

        return $row === null ? null : $this->mapRowToCategory($row);
    }

    public function findBySlugInGroup(CategoryGroup $group, string $slug): ?Category
    {
        $groupId = $group->id();

        if ($groupId === null) {
            return null;
        }

        $row = $this->connection->fetchOne(
            'SELECT id, group_id, parent_id, name, slug, description, sort_order, created_at, updated_at
             FROM categories
             WHERE group_id = :group_id
               AND slug = :slug
             LIMIT 1',
            [
                'group_id' => $groupId,
                'slug' => trim($slug),
            ]
        );

        return $row === null ? null : $this->mapRowToCategory($row);
    }

    public function findCategoriesByGroup(CategoryGroup $group): array
    {
        $groupId = $group->id();

        if ($groupId === null) {
            return [];
        }

        $rows = $this->connection->fetchAll(
            'SELECT id, group_id, parent_id, name, slug, description, sort_order, created_at, updated_at
             FROM categories
             WHERE group_id = :group_id
             ORDER BY sort_order ASC, name ASC, id ASC',
            ['group_id' => $groupId]
        );

        return array_map(fn (array $row): Category => $this->mapRowToCategory($row), $rows);
    }

    public function findRootCategoriesByGroup(CategoryGroup $group): array
    {
        $groupId = $group->id();

        if ($groupId === null) {
            return [];
        }

        $rows = $this->connection->fetchAll(
            'SELECT id, group_id, parent_id, name, slug, description, sort_order, created_at, updated_at
             FROM categories
             WHERE group_id = :group_id
               AND parent_id IS NULL
             ORDER BY sort_order ASC, name ASC, id ASC',
            ['group_id' => $groupId]
        );

        return array_map(fn (array $row): Category => $this->mapRowToCategory($row), $rows);
    }

    public function findChildrenOf(Category $category): array
    {
        $categoryId = $category->id();

        if ($categoryId === null) {
            return [];
        }

        $rows = $this->connection->fetchAll(
            'SELECT id, group_id, parent_id, name, slug, description, sort_order, created_at, updated_at
             FROM categories
             WHERE parent_id = :parent_id
             ORDER BY sort_order ASC, name ASC, id ASC',
            ['parent_id' => $categoryId]
        );

        return array_map(fn (array $row): Category => $this->mapRowToCategory($row), $rows);
    }

    public function findCategoriesForContentItem(ContentItem $item): array
    {
        $itemId = $item->id();

        if ($itemId === null) {
            return [];
        }

        $rows = $this->connection->fetchAll(
            'SELECT c.id, c.group_id, c.parent_id, c.name, c.slug, c.description, c.sort_order, c.created_at, c.updated_at
             FROM categories c
             INNER JOIN content_item_categories cic ON cic.category_id = c.id
             WHERE cic.content_item_id = :content_item_id
             ORDER BY c.group_id ASC, c.sort_order ASC, c.name ASC, c.id ASC',
            ['content_item_id' => $itemId]
        );

        return array_map(fn (array $row): Category => $this->mapRowToCategory($row), $rows);
    }

    public function attachCategoryToContentItem(ContentItem $item, Category $category): void
    {
        $itemId = $item->id();
        $categoryId = $category->id();

        if ($itemId === null || $categoryId === null) {
            throw new RuntimeException('Cannot attach category without persisted content item and category IDs.');
        }

        $existing = $this->connection->fetchOne(
            'SELECT content_item_id
             FROM content_item_categories
             WHERE content_item_id = :content_item_id
               AND category_id = :category_id
             LIMIT 1',
            [
                'content_item_id' => $itemId,
                'category_id' => $categoryId,
            ]
        );

        if ($existing !== null) {
            return;
        }

        $allowedGroupRow = $this->connection->fetchOne(
            'SELECT ctcg.content_type_id
             FROM content_items ci
             INNER JOIN content_type_category_groups ctcg ON ctcg.content_type_id = ci.content_type_id
             WHERE ci.id = :content_item_id
               AND ctcg.category_group_id = :category_group_id
             LIMIT 1',
            [
                'content_item_id' => $itemId,
                'category_group_id' => $category->groupId(),
            ]
        );

        if ($allowedGroupRow === null) {
            throw new RuntimeException('Cannot attach category from a group that is not allowed for this content type.');
        }

        $this->connection->execute(
            'INSERT INTO content_item_categories (content_item_id, category_id)
             VALUES (:content_item_id, :category_id)',
            [
                'content_item_id' => $itemId,
                'category_id' => $categoryId,
            ]
        );
    }

    public function detachCategoryFromContentItem(ContentItem $item, Category $category): void
    {
        $itemId = $item->id();
        $categoryId = $category->id();

        if ($itemId === null || $categoryId === null) {
            return;
        }

        $this->connection->execute(
            'DELETE FROM content_item_categories
             WHERE content_item_id = :content_item_id
               AND category_id = :category_id',
            [
                'content_item_id' => $itemId,
                'category_id' => $categoryId,
            ]
        );
    }

    public function remove(Category $category): void
    {
        $categoryId = $category->id();

        if ($categoryId === null) {
            throw new RuntimeException('Cannot remove category without ID.');
        }

        $affectedRows = $this->connection->execute(
            'DELETE FROM categories
             WHERE id = :id',
            ['id' => $categoryId]
        );

        if ($affectedRows < 1) {
            throw new RuntimeException(sprintf('Category "%s" was not found for removal.', $category->slug()->value()));
        }
    }

    public function isAssignedToContentItems(Category $category): bool
    {
        $categoryId = $category->id();

        if ($categoryId === null) {
            return false;
        }

        $row = $this->connection->fetchOne(
            'SELECT content_item_id
             FROM content_item_categories
             WHERE category_id = :category_id
             LIMIT 1',
            ['category_id' => $categoryId]
        );

        return $row !== null;
    }

    public function hasChildren(Category $category): bool
    {
        $categoryId = $category->id();

        if ($categoryId === null) {
            return false;
        }

        $row = $this->connection->fetchOne(
            'SELECT id
             FROM categories
             WHERE parent_id = :parent_id
             LIMIT 1',
            ['parent_id' => $categoryId]
        );

        return $row !== null;
    }

    private function create(Category $category): Category
    {
        $id = $this->connection->insertAndGetId(
            'INSERT INTO categories (group_id, parent_id, name, slug, description, sort_order, created_at, updated_at)
             VALUES (:group_id, :parent_id, :name, :slug, :description, :sort_order, :created_at, :updated_at)',
            [
                'group_id' => $category->groupId(),
                'parent_id' => $category->parentId(),
                'name' => $category->name(),
                'slug' => $category->slug()->value(),
                'description' => $category->description(),
                'sort_order' => $category->sortOrder(),
                'created_at' => $category->createdAt()->format('Y-m-d H:i:s'),
                'updated_at' => $category->updatedAt()->format('Y-m-d H:i:s'),
            ]
        );

        return $this->findById((int) $id) ?? throw new RuntimeException('Unable to load created category.');
    }

    private function update(Category $category): Category
    {
        $id = $category->id();

        if ($id === null) {
            throw new RuntimeException('Cannot update category without ID.');
        }

        $this->connection->execute(
            'UPDATE categories
             SET group_id = :group_id,
                 parent_id = :parent_id,
                 name = :name,
                 slug = :slug,
                 description = :description,
                 sort_order = :sort_order,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'id' => $id,
                'group_id' => $category->groupId(),
                'parent_id' => $category->parentId(),
                'name' => $category->name(),
                'slug' => $category->slug()->value(),
                'description' => $category->description(),
                'sort_order' => $category->sortOrder(),
                'updated_at' => $category->updatedAt()->format('Y-m-d H:i:s'),
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Unable to load updated category.');
    }

    /** @param array<string, mixed> $row */
    private function mapRowToCategory(array $row): Category
    {
        return new Category(
            id: isset($row['id']) ? (int) $row['id'] : null,
            groupId: (int) ($row['group_id'] ?? 0),
            parentId: isset($row['parent_id']) ? (int) $row['parent_id'] : null,
            name: (string) ($row['name'] ?? ''),
            slug: Slug::fromString((string) ($row['slug'] ?? '')),
            description: isset($row['description']) ? (string) $row['description'] : null,
            sortOrder: (int) ($row['sort_order'] ?? 0),
            createdAt: new DateTimeImmutable((string) ($row['created_at'] ?? 'now')),
            updatedAt: new DateTimeImmutable((string) ($row['updated_at'] ?? 'now'))
        );
    }
}
