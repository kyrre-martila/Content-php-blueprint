<?php

declare(strict_types=1);

namespace App\Application\Content\Dto;

final class ContentItemInput
{
    /**
     * @param list<array{pattern: string, data: array<string, string>}> $patternBlocks
     * @param array<string,mixed> $fieldValues
     */
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
        public readonly string $status,
        public readonly string $contentType,
        public readonly ?string $body = null,
        public readonly array $patternBlocks = [],
        public readonly array $fieldValues = [],
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaDescription = null,
        public readonly ?string $ogImage = null,
        public readonly ?string $canonicalUrl = null,
        public readonly bool $noindex = false
    ) {
    }
}
