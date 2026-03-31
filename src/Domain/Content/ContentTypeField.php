<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Content\Exception\InvalidContentTypeFieldException;
use DateTimeImmutable;

final class ContentTypeField
{
    /** @var list<string> */
    private const ALLOWED_FIELD_TYPES = [
        'text',
        'textarea',
        'richtext',
        'number',
        'boolean',
        'date',
        'image',
        'file',
        'select',
    ];

    /**
     * @param array<string, mixed>|null $settings
     */
    public function __construct(
        private readonly ?int $id,
        private readonly int $contentTypeId,
        private readonly string $name,
        private readonly string $label,
        private readonly string $fieldType,
        private readonly bool $isRequired,
        private readonly ?string $defaultValue,
        private readonly ?array $settings,
        private readonly int $sortOrder,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {
        $this->assertIdIsValid($id);
        $this->assertContentTypeIdIsValid($contentTypeId);
        $this->assertNameIsValid($name);
        $this->assertLabelIsValid($label);
        $this->assertFieldTypeIsValid($fieldType);
        $this->assertSortOrderIsValid($sortOrder);
    }

    public static function isSupportedFieldType(string $fieldType): bool
    {
        return in_array($fieldType, self::ALLOWED_FIELD_TYPES, true);
    }

    /** @return list<string> */
    public static function supportedFieldTypes(): array
    {
        return self::ALLOWED_FIELD_TYPES;
    }

    public function id(): ?int { return $this->id; }
    public function contentTypeId(): int { return $this->contentTypeId; }
    public function name(): string { return $this->name; }
    public function label(): string { return $this->label; }
    public function fieldType(): string { return $this->fieldType; }
    public function isRequired(): bool { return $this->isRequired; }
    public function defaultValue(): ?string { return $this->defaultValue; }
    /** @return array<string,mixed>|null */
    public function settings(): ?array { return $this->settings; }
    public function sortOrder(): int { return $this->sortOrder; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }

    /** @return array{key:string,type:string,required:bool} */
    public function toPortableFieldDefinition(): array
    {
        return [
            'key' => $this->name,
            'type' => $this->fieldType,
            'required' => $this->isRequired,
        ];
    }

    private function assertIdIsValid(?int $id): void
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidContentTypeFieldException('Field id must be a positive integer when provided.');
        }
    }

    private function assertContentTypeIdIsValid(int $contentTypeId): void
    {
        if ($contentTypeId <= 0) {
            throw new InvalidContentTypeFieldException('contentTypeId must be a positive integer.');
        }
    }

    private function assertNameIsValid(string $name): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new InvalidContentTypeFieldException('Field name must start with a letter and contain only lowercase letters, numbers, and underscores.');
        }
    }

    private function assertLabelIsValid(string $label): void
    {
        if (trim($label) === '') {
            throw new InvalidContentTypeFieldException('Field label cannot be empty.');
        }
    }

    private function assertFieldTypeIsValid(string $fieldType): void
    {
        if (!self::isSupportedFieldType($fieldType)) {
            throw new InvalidContentTypeFieldException(sprintf('Unsupported field type "%s".', $fieldType));
        }
    }

    private function assertSortOrderIsValid(int $sortOrder): void
    {
        if ($sortOrder < 0) {
            throw new InvalidContentTypeFieldException('sortOrder must be zero or greater.');
        }
    }
}
