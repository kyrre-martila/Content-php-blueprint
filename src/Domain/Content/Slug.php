<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Content\Exception\InvalidSlugException;

final class Slug
{
    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalizedValue = self::normalize($value);

        self::assertValid($normalizedValue);

        return new self($normalizedValue);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function normalize(string $value): string
    {
        $normalizedValue = trim($value);
        $normalizedValue = strtolower($normalizedValue);

        $normalizedValue = preg_replace('/[^a-z0-9]+/', '-', $normalizedValue) ?? $normalizedValue;
        $normalizedValue = preg_replace('/-{2,}/', '-', $normalizedValue) ?? $normalizedValue;

        return trim($normalizedValue, '-');
    }

    private static function assertValid(string $value): void
    {
        if ($value === '') {
            throw new InvalidSlugException('Slug cannot be empty.');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new InvalidSlugException('Slug must contain only lowercase letters, numbers, and hyphens.');
        }
    }
}
