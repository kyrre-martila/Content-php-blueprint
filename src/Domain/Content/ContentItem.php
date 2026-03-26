<?php

declare(strict_types=1);

namespace App\Domain\Content;

use DateTimeImmutable;
use InvalidArgumentException;

final class ContentItem
{
    public function __construct(
        private readonly ?int $id,
        private readonly ContentType $type,
        private readonly string $title,
        private readonly Slug $slug,
        private readonly ContentStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if (trim($this->title) === '') {
            throw new InvalidArgumentException('Content item title cannot be empty.');
        }
    }

    public static function draft(
        ?int $id,
        ContentType $type,
        string $title,
        Slug $slug,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            $id,
            $type,
            $title,
            $slug,
            ContentStatus::Draft,
            $createdAt,
            $updatedAt
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function type(): ContentType
    {
        return $this->type;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function status(): ContentStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    public function withStatus(ContentStatus $status, DateTimeImmutable $updatedAt): self
    {
        return new self(
            $this->id,
            $this->type,
            $this->title,
            $this->slug,
            $status,
            $this->createdAt,
            $updatedAt
        );
    }

    public function publish(DateTimeImmutable $updatedAt): self
    {
        return $this->withStatus(ContentStatus::Published, $updatedAt);
    }

    public function withTitle(string $title, DateTimeImmutable $updatedAt): self
    {
        return new self(
            $this->id,
            $this->type,
            $title,
            $this->slug,
            $this->status,
            $this->createdAt,
            $updatedAt
        );
    }

    public function withSlug(Slug $slug, DateTimeImmutable $updatedAt): self
    {
        return new self(
            $this->id,
            $this->type,
            $this->title,
            $slug,
            $this->status,
            $this->createdAt,
            $updatedAt
        );
    }
}
