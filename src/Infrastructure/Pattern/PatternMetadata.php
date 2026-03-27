<?php

declare(strict_types=1);

namespace App\Infrastructure\Pattern;

use InvalidArgumentException;

final class PatternMetadata
{
    /** @var list<array{name: string, type: string}> */
    private array $fields;

    /**
     * @param list<array{name: string, type: string}> $fields
     */
    private function __construct(
        private readonly string $name,
        private readonly string $key,
        private readonly string $description,
        array $fields
    ) {
        $this->fields = $fields;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function fromArray(array $metadata): self
    {
        foreach (['name', 'key', 'description', 'fields'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $metadata)) {
                throw new InvalidArgumentException(sprintf('Missing required pattern metadata key: %s', $requiredKey));
            }
        }

        $name = self::nonEmptyString($metadata['name'] ?? null, 'name');
        $key = self::nonEmptyString($metadata['key'] ?? null, 'key');
        $description = self::nonEmptyString($metadata['description'] ?? null, 'description');
        $fields = $metadata['fields'];

        if (!is_array($fields)) {
            throw new InvalidArgumentException('Pattern metadata key "fields" must be an array.');
        }

        $validatedFields = [];

        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                throw new InvalidArgumentException(sprintf('Pattern field at index %d must be an object.', $index));
            }

            $fieldName = self::nonEmptyString($field['name'] ?? null, sprintf('fields[%d].name', $index));
            $fieldType = self::nonEmptyString($field['type'] ?? null, sprintf('fields[%d].type', $index));

            if (!in_array($fieldType, ['text', 'textarea', 'image'], true)) {
                throw new InvalidArgumentException(sprintf('Unsupported field type "%s".', $fieldType));
            }

            $validatedFields[] = [
                'name' => $fieldName,
                'type' => $fieldType,
            ];
        }

        return new self($name, $key, $description, $validatedFields);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return list<array{name: string, type: string}>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * @return array{name: string, key: string, description: string, fields: list<array{name: string, type: string}>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description,
            'fields' => $this->fields,
        ];
    }

    private static function nonEmptyString(mixed $value, string $fieldName): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('Pattern metadata key "%s" must be a string.', $fieldName));
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException(sprintf('Pattern metadata key "%s" must be a non-empty string.', $fieldName));
        }

        return $trimmed;
    }
}
