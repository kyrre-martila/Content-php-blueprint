<?php

declare(strict_types=1);

namespace App\Infrastructure\Pattern;

use InvalidArgumentException;

final class PatternDataValidator
{
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validate(PatternMetadata $metadata, array $data): array
    {
        $allowedFields = [];

        foreach ($metadata->fields() as $field) {
            $type = $field['type'];

            if (!in_array($type, ['text', 'textarea'], true)) {
                throw new InvalidArgumentException(sprintf(
                    'Unsupported pattern field type "%s" for "%s".',
                    $type,
                    $field['name']
                ));
            }

            $allowedFields[$field['name']] = $type;
        }

        foreach ($data as $key => $_value) {
            if (!array_key_exists($key, $allowedFields)) {
                throw new InvalidArgumentException(sprintf('Unknown pattern field "%s".', $key));
            }
        }

        $validated = [];

        foreach ($allowedFields as $fieldName => $_fieldType) {
            $value = $data[$fieldName] ?? '';

            if (!is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException(sprintf('Invalid value for pattern field "%s".', $fieldName));
            }

            $validated[$fieldName] = $value === null ? '' : (string) $value;
        }

        return $validated;
    }
}
