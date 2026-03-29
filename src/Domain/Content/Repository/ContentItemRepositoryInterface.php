<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentType;
use App\Domain\Content\Slug;

interface ContentItemRepositoryInterface
{
    public const DEFAULT_LIMIT = 20;
    public const DEFAULT_OFFSET = 0;

    public function save(ContentItem $contentItem): ContentItem;

    public function findById(int $id): ?ContentItem;

    public function findBySlug(Slug $slug): ?ContentItem;

    /**
     * @return array{
     *   items: list<ContentItem>,
     *   total_count: int,
     *   limit: int,
     *   offset: int
     * }
     */
    public function findByType(
        ContentType $contentType,
        int $limit = self::DEFAULT_LIMIT,
        int $offset = self::DEFAULT_OFFSET
    ): array;

    /**
     * @return array{
     *   items: array<string, list<ContentItem>>,
     *   total_count: int,
     *   limit: int,
     *   offset: int
     * }
     */
    public function findAllWithTypes(
        int $limit = self::DEFAULT_LIMIT,
        int $offset = self::DEFAULT_OFFSET
    ): array;

    /**
     * @return array{
     *   items: list<ContentItem>,
     *   total_count: int,
     *   limit: int,
     *   offset: int
     * }
     */
    public function findPublished(
        int $limit = self::DEFAULT_LIMIT,
        int $offset = self::DEFAULT_OFFSET
    ): array;

    /**
     * @return array{
     *   items: list<ContentItem>,
     *   total_count: int,
     *   limit: int,
     *   offset: int
     * }
     */
    public function findPublishedByType(
        ContentType $contentType,
        int $limit = self::DEFAULT_LIMIT,
        int $offset = self::DEFAULT_OFFSET
    ): array;

    public function remove(ContentItem $contentItem): void;
}
