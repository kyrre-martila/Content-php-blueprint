<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Content\Exception\InvalidContentTypeException;

enum ContentViewType: string
{
    case SINGLE = 'single';
    case COLLECTION = 'collection';

    public static function fromString(string $value): self
    {
        return match (strtolower(trim($value))) {
            self::SINGLE->value => self::SINGLE,
            self::COLLECTION->value => self::COLLECTION,
            default => throw new InvalidContentTypeException(
                sprintf('Invalid content view type "%s". Allowed values are "single" and "collection".', $value)
            ),
        };
    }
}
