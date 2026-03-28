<?php

declare(strict_types=1);

namespace App\Application\Validation;

final class ValidationResult
{
    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $values
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
        public readonly array $values = []
    ) {
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function valid(array $values = []): self
    {
        return new self(true, [], $values);
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $values
     */
    public static function invalid(array $errors, array $values = []): self
    {
        return new self(false, $errors, $values);
    }
}
