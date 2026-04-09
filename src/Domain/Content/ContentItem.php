<?php

declare(strict_types=1);

namespace App\Domain\Content;

use DateTimeImmutable;
use InvalidArgumentException;

final class ContentItem
{
    /**
     * @param list<array{pattern: string, data: array<string, string>}> $patternBlocks
     * @param array<string, mixed> $fieldValues
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
        private readonly array $fieldValues = [],
        private readonly ?string $metaTitle = null,
        private readonly ?string $metaDescription = null,
        private readonly ?string $ogImage = null,
        private readonly ?string $canonicalUrl = null,
        private readonly bool $noindex = false,
        private readonly ?int $parentId = null,
        private readonly int $sortOrder = 0
    ) {
        if (trim($this->title) === '') {
            throw new InvalidArgumentException('Content item title cannot be empty.');
        }

        if ($this->parentId !== null && $this->parentId < 1) {
            throw new InvalidArgumentException('Content item parent ID must be a positive integer when provided.');
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

    public function id(): ?int { return $this->id; }
    public function type(): ContentType { return $this->type; }
    public function title(): string { return $this->title; }
    public function slug(): Slug { return $this->slug; }
    public function status(): ContentStatus { return $this->status; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }

    /** @return list<array{pattern: string, data: array<string, string>}> */
    public function patternBlocks(): array { return $this->patternBlocks; }

    /** @return array<string, mixed> */
    public function fieldValues(): array
    {
        $values = [];

        foreach ($this->type->fields() as $field) {
            $name = $field->name();

            if (array_key_exists($name, $this->fieldValues)) {
                $values[$name] = $this->normalizeFieldValue($field->fieldType(), $this->fieldValues[$name]);
                continue;
            }

            $defaultValue = $field->defaultValue();
            $values[$name] = $this->normalizeFieldValue($field->fieldType(), $defaultValue);
        }

        foreach ($this->fieldValues as $name => $value) {
            if (!is_string($name) || array_key_exists($name, $values)) {
                continue;
            }

            $values[$name] = $value;
        }

        return $values;
    }

    private function normalizeFieldValue(string $fieldType, mixed $value): mixed
    {
        if (!in_array($fieldType, ['image', 'file'], true)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_float($value) && floor($value) === $value) {
            $integerValue = (int) $value;

            return $integerValue > 0 ? $integerValue : null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        if (ctype_digit($normalized)) {
            $integerValue = (int) $normalized;

            return $integerValue > 0 ? $integerValue : null;
        }

        // Backward compatibility for legacy URL/string values.
        return $normalized;
    }

    public function fieldValue(string $name): mixed
    {
        $values = $this->fieldValues();

        return $values[$name] ?? null;
    }

    public function metaTitle(): ?string { return $this->metaTitle; }
    public function metaDescription(): ?string { return $this->metaDescription; }
    public function ogImage(): ?string { return $this->ogImage; }
    public function canonicalUrl(): ?string { return $this->canonicalUrl; }
    public function noindex(): bool { return $this->noindex; }
    public function parentId(): ?int { return $this->parentId; }
    public function sortOrder(): int { return $this->sortOrder; }

    public function isRoot(): bool { return $this->parentId === null; }
    public function hasParent(): bool { return $this->parentId !== null; }
    public function isPublished(): bool { return $this->status->isPublished(); }

    public function withStatus(ContentStatus $status, DateTimeImmutable $updatedAt): self
    {
        return new self($this->id, $this->type, $this->title, $this->slug, $status, $this->createdAt, $updatedAt, $this->patternBlocks, $this->fieldValues, $this->metaTitle, $this->metaDescription, $this->ogImage, $this->canonicalUrl, $this->noindex, $this->parentId, $this->sortOrder);
    }

    public function publish(DateTimeImmutable $updatedAt): self { return $this->withStatus(ContentStatus::Published, $updatedAt); }

    public function withTitle(string $title, DateTimeImmutable $updatedAt): self
    {
        return new self($this->id, $this->type, $title, $this->slug, $this->status, $this->createdAt, $updatedAt, $this->patternBlocks, $this->fieldValues, $this->metaTitle, $this->metaDescription, $this->ogImage, $this->canonicalUrl, $this->noindex, $this->parentId, $this->sortOrder);
    }

    public function withSlug(Slug $slug, DateTimeImmutable $updatedAt): self
    {
        return new self($this->id, $this->type, $this->title, $slug, $this->status, $this->createdAt, $updatedAt, $this->patternBlocks, $this->fieldValues, $this->metaTitle, $this->metaDescription, $this->ogImage, $this->canonicalUrl, $this->noindex, $this->parentId, $this->sortOrder);
    }

    /** @param list<array{pattern: string, data: array<string, string>}> $patternBlocks */
    public function withPatternBlocks(array $patternBlocks, DateTimeImmutable $updatedAt): self
    {
        return new self($this->id, $this->type, $this->title, $this->slug, $this->status, $this->createdAt, $updatedAt, $patternBlocks, $this->fieldValues, $this->metaTitle, $this->metaDescription, $this->ogImage, $this->canonicalUrl, $this->noindex, $this->parentId, $this->sortOrder);
    }

    /** @param array<string,mixed> $fieldValues */
    public function withFieldValues(array $fieldValues, DateTimeImmutable $updatedAt): self
    {
        return new self($this->id, $this->type, $this->title, $this->slug, $this->status, $this->createdAt, $updatedAt, $this->patternBlocks, $fieldValues, $this->metaTitle, $this->metaDescription, $this->ogImage, $this->canonicalUrl, $this->noindex, $this->parentId, $this->sortOrder);
    }

    public function withSeoMetadata(?string $metaTitle, ?string $metaDescription, ?string $ogImage, ?string $canonicalUrl, bool $noindex, DateTimeImmutable $updatedAt): self
    {
        return new self($this->id, $this->type, $this->title, $this->slug, $this->status, $this->createdAt, $updatedAt, $this->patternBlocks, $this->fieldValues, $metaTitle, $metaDescription, $ogImage, $canonicalUrl, $noindex, $this->parentId, $this->sortOrder);
    }

    public function withHierarchy(?int $parentId, int $sortOrder, DateTimeImmutable $updatedAt): self
    {
        return new self($this->id, $this->type, $this->title, $this->slug, $this->status, $this->createdAt, $updatedAt, $this->patternBlocks, $this->fieldValues, $this->metaTitle, $this->metaDescription, $this->ogImage, $this->canonicalUrl, $this->noindex, $parentId, $sortOrder);
    }
}
