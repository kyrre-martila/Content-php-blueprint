<?php

declare(strict_types=1);

namespace App\Domain\Content;

use DateTimeImmutable;
use InvalidArgumentException;

final class ContentRelationship
{
    private const RELATION_TYPE_MAX_LENGTH = 60;
    private const RELATION_TYPE_PATTERN = '/^[a-z]*$/';

    public function __construct(
        private readonly ?int $id,
        private readonly int $fromContentItemId,
        private readonly int $toContentItemId,
        private readonly string $relationType,
        private readonly int $sortOrder,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($this->fromContentItemId < 1) {
            throw new InvalidArgumentException('From content item ID must be a positive integer.');
        }

        if ($this->toContentItemId < 1) {
            throw new InvalidArgumentException('To content item ID must be a positive integer.');
        }

        if (trim($this->relationType) === '') {
            throw new InvalidArgumentException('Relationship type cannot be empty.');
        }

        if (mb_strlen($this->relationType) > self::RELATION_TYPE_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Relationship type must be %d characters or fewer.',
                self::RELATION_TYPE_MAX_LENGTH
            ));
        }

        if (preg_match(self::RELATION_TYPE_PATTERN, $this->relationType) !== 1) {
            throw new InvalidArgumentException('Relationship type must contain lowercase letters only (a-z).');
        }

        if ($this->fromContentItemId === $this->toContentItemId) {
            throw new InvalidArgumentException('Self-referencing relationships are not currently allowed.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function fromContentItemId(): int
    {
        return $this->fromContentItemId;
    }

    public function toContentItemId(): int
    {
        return $this->toContentItemId;
    }

    public function relationType(): string
    {
        return $this->relationType;
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
}
