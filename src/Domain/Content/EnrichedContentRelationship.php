<?php

declare(strict_types=1);

namespace App\Domain\Content;

use InvalidArgumentException;

final class EnrichedContentRelationship
{
    public function __construct(
        private readonly int $fromContentItemId,
        private readonly int $toContentItemId,
        private readonly string $relationType,
        private readonly int $sortOrder,
        private readonly string $fromContentItemTitle,
        private readonly string $toContentItemTitle,
        private readonly string $fromContentTypeSlug,
        private readonly string $toContentTypeSlug,
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

        if (trim($this->fromContentItemTitle) === '') {
            throw new InvalidArgumentException('From content item title cannot be empty.');
        }

        if (trim($this->toContentItemTitle) === '') {
            throw new InvalidArgumentException('To content item title cannot be empty.');
        }

        if (trim($this->fromContentTypeSlug) === '') {
            throw new InvalidArgumentException('From content type slug cannot be empty.');
        }

        if (trim($this->toContentTypeSlug) === '') {
            throw new InvalidArgumentException('To content type slug cannot be empty.');
        }
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

    public function fromContentItemTitle(): string
    {
        return $this->fromContentItemTitle;
    }

    public function toContentItemTitle(): string
    {
        return $this->toContentItemTitle;
    }

    public function fromContentTypeSlug(): string
    {
        return $this->fromContentTypeSlug;
    }

    public function toContentTypeSlug(): string
    {
        return $this->toContentTypeSlug;
    }
}
