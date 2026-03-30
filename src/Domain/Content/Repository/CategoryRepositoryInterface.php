<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\Category;
use App\Domain\Content\CategoryGroup;
use App\Domain\Content\ContentItem;

interface CategoryRepositoryInterface
{
    public function save(Category $category): Category;

    public function findById(int $id): ?Category;

    /**
     * @return list<Category>
     */
    public function findCategoriesByGroup(CategoryGroup $group): array;

    /**
     * @return list<Category>
     */
    public function findRootCategoriesByGroup(CategoryGroup $group): array;

    /**
     * @return list<Category>
     */
    public function findChildrenOf(Category $category): array;

    /**
     * @return list<Category>
     */
    public function findCategoriesForContentItem(ContentItem $item): array;

    public function attachCategoryToContentItem(ContentItem $item, Category $category): void;

    public function detachCategoryFromContentItem(ContentItem $item, Category $category): void;

    public function remove(Category $category): void;

    public function isAssignedToContentItems(Category $category): bool;

    public function hasChildren(Category $category): bool;
}
