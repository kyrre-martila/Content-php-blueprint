<?php

declare(strict_types=1);

namespace App\Domain\Content;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public static function fromString(string $value): self
    {
        $normalizedValue = strtolower(trim($value));

        return self::from($normalizedValue);
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isPublished(): bool
    {
        return $this === self::Published;
    }
}
