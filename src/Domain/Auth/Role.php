<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use InvalidArgumentException;

final class Role
{
    public const SUPERADMIN = 'superadmin';
    public const ADMIN = 'admin';
    public const EDITOR = 'editor';

    /**
     * @var array<int, string>
     */
    private const ALLOWED = [
        self::SUPERADMIN,
        self::ADMIN,
        self::EDITOR,
    ];

    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if (!in_array($normalized, self::ALLOWED, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported role "%s".', $value));
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
