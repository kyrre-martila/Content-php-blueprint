<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\ContentType;
use App\Domain\Content\CategoryGroup;

interface ContentTypeRepositoryInterface
{
    /**
     * Persists name, label, template, and view type configuration for a content type.
     */
    public function save(ContentType $contentType): ContentType;

    public function findByName(string $name): ?ContentType;

    /**
     * @return list<ContentType>
     */
    public function findAll(): array;

    /**
     * @return list<CategoryGroup>
     */
    public function getAllowedCategoryGroups(ContentType $type): array;

    public function attachCategoryGroup(ContentType $type, CategoryGroup $group): void;

    public function detachCategoryGroup(ContentType $type, CategoryGroup $group): void;

    public function remove(ContentType $contentType): void;
}
