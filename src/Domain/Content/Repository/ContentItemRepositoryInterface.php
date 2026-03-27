<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentType;
use App\Domain\Content\Slug;

interface ContentItemRepositoryInterface
{
    public function save(ContentItem $contentItem): ContentItem;

    public function findById(int $id): ?ContentItem;

    public function findBySlug(Slug $slug): ?ContentItem;

    /**
     * @return list<ContentItem>
     */
    public function findByType(ContentType $contentType): array;

    /**
     * @return list<ContentItem>
     */
    public function findPublished(): array;

    public function remove(ContentItem $contentItem): void;
}
