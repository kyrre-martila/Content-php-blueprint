<?php

declare(strict_types=1);

namespace App\Domain\Pattern;

use InvalidArgumentException;

final class PatternMetadataValidator
{
    public function __construct(private readonly string $metadataPath)
    {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function validate(array $metadata): void
    {
        foreach (['name', 'description', 'fields', 'category'] as $requiredField) {
            if (!array_key_exists($requiredField, $metadata)) {
                throw $this->invalid(sprintf('missing "%s"', $requiredField));
            }
        }

        if (!is_array($metadata['fields'])) {
            throw $this->invalid('"fields" must be an array');
        }

        foreach ($metadata['fields'] as $index => $field) {
            if (!is_array($field)) {
                throw $this->invalid(sprintf('field definition at index %d must be an object', $index));
            }

            foreach (['name', 'type', 'label'] as $requiredFieldProperty) {
                if (!array_key_exists($requiredFieldProperty, $field)) {
                    throw $this->invalid(sprintf('field at index %d missing "%s"', $index, $requiredFieldProperty));
                }
            }
        }
    }

    private function invalid(string $reason): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf('Pattern metadata invalid in %s: %s', $this->metadataPath, $reason));
    }
}
