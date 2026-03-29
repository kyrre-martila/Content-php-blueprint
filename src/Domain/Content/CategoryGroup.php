<?php

declare(strict_types=1);

namespace App\Domain\Content;

use DateTimeImmutable;
use InvalidArgumentException;

final class CategoryGroup
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $name,
        private readonly Slug $slug,
        private readonly ?string $description,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Category group name cannot be empty.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
