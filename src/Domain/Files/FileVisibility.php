<?php

declare(strict_types=1);

namespace App\Domain\Files;

enum FileVisibility: string
{
    case Public = 'public';
    case Authenticated = 'authenticated';
    case Private = 'private';

    public static function fromString(string $value): self
    {
        return self::from(strtolower(trim($value)));
    }
}
