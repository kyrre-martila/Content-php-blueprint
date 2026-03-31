<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use InvalidArgumentException;

final class Role
{
    private const SUPERADMIN = 'superadmin';
    private const ADMIN = 'admin';
    private const EDITOR = 'editor';

    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if (!in_array($normalized, self::allowedValues(), true)) {
            throw new InvalidArgumentException(sprintf('Unsupported role "%s".', $value));
        }

        return new self($normalized);
    }

    public static function superadmin(): self
    {
        return new self(self::SUPERADMIN);
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function editor(): self
    {
        return new self(self::EDITOR);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @return array<int, string>
     */
    private static function allowedValues(): array
    {
        return [
            self::SUPERADMIN,
            self::ADMIN,
            self::EDITOR,
        ];
    }
}
