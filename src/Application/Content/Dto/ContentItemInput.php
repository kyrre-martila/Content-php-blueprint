<?php

declare(strict_types=1);

namespace App\Application\Content\Dto;

final class ContentItemInput
{
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
        public readonly string $status,
        public readonly string $contentType,
        public readonly ?string $body = null
    ) {
    }
}
