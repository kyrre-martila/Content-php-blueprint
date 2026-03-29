<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\CategoryGroup;

interface CategoryGroupRepositoryInterface
{
    public function save(CategoryGroup $group): CategoryGroup;

    public function findById(int $id): ?CategoryGroup;

    public function findBySlug(string $slug): ?CategoryGroup;

    /**
     * @return list<CategoryGroup>
     */
    public function findAllGroups(): array;
}
