<?php

declare(strict_types=1);

namespace App\Application\Content\Dto;

use App\Domain\Content\ContentItem;

final class ContentItemValidationResult
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors,
        public readonly ?ContentItem $contentItem = null
    ) {
    }

    /**
     * @param array<string, string> $errors
     */
    public static function invalid(array $errors): self
    {
        return new self(false, $errors, null);
    }

    public static function valid(ContentItem $contentItem): self
    {
        return new self(true, [], $contentItem);
    }
}
