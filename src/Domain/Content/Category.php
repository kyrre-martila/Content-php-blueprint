<?php

declare(strict_types=1);

namespace App\Domain\Content;

use DateTimeImmutable;
use InvalidArgumentException;

final class Category
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $groupId,
        private readonly ?int $parentId,
        private readonly string $name,
        private readonly Slug $slug,
        private readonly ?string $description,
        private readonly int $sortOrder,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->groupId < 1) {
            throw new InvalidArgumentException('Category group ID must be a positive integer.');
        }

        if ($this->parentId !== null && $this->parentId < 1) {
            throw new InvalidArgumentException('Category parent ID must be a positive integer when provided.');
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Category name cannot be empty.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function groupId(): int
    {
        return $this->groupId;
    }

    public function parentId(): ?int
    {
        return $this->parentId;
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

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }
}
