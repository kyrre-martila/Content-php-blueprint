<?php

declare(strict_types=1);

namespace App\Application\Content\Dto;

final class ContentItemSummary
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $status,
        public readonly string $contentType,
        public readonly string $updatedAt
    ) {
    }
}
