<?php

declare(strict_types=1);

namespace App\Domain\Content;

use DateTimeImmutable;
use InvalidArgumentException;

final class ContentItem
{
    /**
     * @param list<array{pattern: string, data: array<string, string>}> $patternBlocks
     */
    public function __construct(
        private readonly ?int $id,
        private readonly ContentType $type,
        private readonly string $title,
        private readonly Slug $slug,
        private readonly ContentStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
        private readonly array $patternBlocks = [],
        private readonly ?string $metaTitle = null,
        private readonly ?string $metaDescription = null,
        private readonly ?string $ogImage = null,
        private readonly ?string $canonicalUrl = null,
        private readonly bool $noindex = false
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

    /**
     * @return list<array{pattern: string, data: array<string, string>}>
     */
    public function patternBlocks(): array
    {
        return $this->patternBlocks;
    }

    public function metaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function metaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function ogImage(): ?string
    {
        return $this->ogImage;
    }

    public function canonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function noindex(): bool
    {
        return $this->noindex;
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
            $updatedAt,
            $this->patternBlocks,
            $this->metaTitle,
            $this->metaDescription,
            $this->ogImage,
            $this->canonicalUrl,
            $this->noindex
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
            $updatedAt,
            $this->patternBlocks,
            $this->metaTitle,
            $this->metaDescription,
            $this->ogImage,
            $this->canonicalUrl,
            $this->noindex
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
            $updatedAt,
            $this->patternBlocks,
            $this->metaTitle,
            $this->metaDescription,
            $this->ogImage,
            $this->canonicalUrl,
            $this->noindex
        );
    }

    /**
     * @param list<array{pattern: string, data: array<string, string>}> $patternBlocks
     */
    public function withPatternBlocks(array $patternBlocks, DateTimeImmutable $updatedAt): self
    {
        return new self(
            $this->id,
            $this->type,
            $this->title,
            $this->slug,
            $this->status,
            $this->createdAt,
            $updatedAt,
            $patternBlocks,
            $this->metaTitle,
            $this->metaDescription,
            $this->ogImage,
            $this->canonicalUrl,
            $this->noindex
        );
    }

    public function withSeoMetadata(
        ?string $metaTitle,
        ?string $metaDescription,
        ?string $ogImage,
        ?string $canonicalUrl,
        bool $noindex,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            $this->id,
            $this->type,
            $this->title,
            $this->slug,
            $this->status,
            $this->createdAt,
            $updatedAt,
            $this->patternBlocks,
            $metaTitle,
            $metaDescription,
            $ogImage,
            $canonicalUrl,
            $noindex
        );
    }
}
