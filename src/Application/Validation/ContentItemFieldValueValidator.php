<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Domain\Content\ContentType;
use App\Domain\Content\ContentTypeField;
use App\Domain\Files\Repository\FileRepositoryInterface;
use DateTimeImmutable;

final class ContentItemFieldValueValidator
{
    public function __construct(
        private readonly ?FileRepositoryInterface $files = null
    ) {
    }

    /**
     * @param array<string,mixed> $inputValues
     */
    public function validate(ContentType $contentType, array $inputValues): ValidationResult
    {
        $errors = [];
        $normalized = [];

        foreach ($contentType->fields() as $field) {
            $name = $field->name();
            $raw = array_key_exists($name, $inputValues) ? $inputValues[$name] : $field->defaultValue();

            if ($raw === '' || $raw === null) {
                $value = null;
            } else {
                $value = $this->normalizeByType($field, $raw, $errors);
            }

            if ($value === null && $field->defaultValue() !== null) {
                $value = $this->normalizeDefaultValue($field);
            }

            if ($field->isRequired() && $this->isMissingRequiredValue($value, $field->fieldType())) {
                $errors['field_values.' . $name] = sprintf('%s is required.', $field->label());
            }

            $normalized[$name] = $value;
        }

        if ($errors !== []) {
            return ValidationResult::invalid($errors, ['field_values' => $normalized]);
        }

        return ValidationResult::valid(['field_values' => $normalized]);
    }

    private function normalizeDefaultValue(ContentTypeField $field): mixed
    {
        $default = $field->defaultValue();

        if ($default === null) {
            return null;
        }

        $errors = [];

        return $this->normalizeByType($field, $default, $errors);
    }

    private function isMissingRequiredValue(mixed $value, string $fieldType): bool
    {
        if ($fieldType === 'boolean') {
            return !is_bool($value);
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return $value === null;
    }

    /** @param array<string,string> $errors */
    private function normalizeByType(ContentTypeField $field, mixed $raw, array &$errors): mixed
    {
        $key = 'field_values.' . $field->name();

        return match ($field->fieldType()) {
            'text', 'textarea', 'richtext' => $this->normalizeString($raw),
            'image', 'file' => $this->normalizeFileReference($field, $raw, $errors, $key),
            'number' => $this->normalizeNumber($field, $raw, $errors, $key),
            'boolean' => $this->normalizeBoolean($raw, $errors, $key),
            'date' => $this->normalizeDate($raw, $errors, $key),
            'select' => $this->normalizeSelect($field, $raw, $errors, $key),
            default => null,
        };
    }

    /** @param array<string,string> $errors */
    private function normalizeFileReference(ContentTypeField $field, mixed $raw, array &$errors, string $errorKey): int|string|null
    {
        if (is_int($raw)) {
            return $this->normalizeFileId($field, $raw, $errors, $errorKey);
        }

        if (is_float($raw) && floor($raw) === $raw) {
            return $this->normalizeFileId($field, (int) $raw, $errors, $errorKey);
        }

        if (!is_scalar($raw)) {
            $errors[$errorKey] = sprintf('%s must reference a valid file.', $field->label());
            return null;
        }

        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return $this->normalizeFileId($field, (int) $value, $errors, $errorKey);
        }

        // Backward compatibility path for legacy rows that persisted URL strings.
        return $value;
    }

    /** @param array<string,string> $errors */
    private function normalizeFileId(ContentTypeField $field, int $id, array &$errors, string $errorKey): ?int
    {
        if ($id < 1) {
            $errors[$errorKey] = sprintf('%s must reference a valid file ID.', $field->label());
            return null;
        }

        if ($this->files !== null && $this->files->findById($id) === null) {
            $errors[$errorKey] = sprintf('%s must reference an existing file.', $field->label());
            return null;
        }

        return $id;
    }

    private function normalizeString(mixed $raw): ?string
    {
        if (!is_scalar($raw)) {
            return null;
        }

        $value = trim((string) $raw);

        return $value === '' ? null : $value;
    }

    /** @param array<string,string> $errors */
    private function normalizeNumber(ContentTypeField $field, mixed $raw, array &$errors, string $errorKey): ?float
    {
        if (!is_scalar($raw) || !is_numeric((string) $raw)) {
            $errors[$errorKey] = sprintf('%s must be a valid number.', $field->label());
            return null;
        }

        $value = (float) $raw;
        $settings = $field->settings() ?? [];

        if (isset($settings['min']) && is_numeric((string) $settings['min']) && $value < (float) $settings['min']) {
            $errors[$errorKey] = sprintf('%s must be at least %s.', $field->label(), (string) $settings['min']);
        }

        if (isset($settings['max']) && is_numeric((string) $settings['max']) && $value > (float) $settings['max']) {
            $errors[$errorKey] = sprintf('%s must be at most %s.', $field->label(), (string) $settings['max']);
        }

        return $value;
    }

    /** @param array<string,string> $errors */
    private function normalizeBoolean(mixed $raw, array &$errors, string $errorKey): ?bool
    {
        if (is_bool($raw)) {
            return $raw;
        }

        if (!is_scalar($raw)) {
            $errors[$errorKey] = 'Boolean value is invalid.';
            return null;
        }

        $normalized = strtolower(trim((string) $raw));

        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        $errors[$errorKey] = 'Boolean value is invalid.';

        return null;
    }

    /** @param array<string,string> $errors */
    private function normalizeDate(mixed $raw, array &$errors, string $errorKey): ?string
    {
        if (!is_scalar($raw)) {
            $errors[$errorKey] = 'Date value is invalid.';
            return null;
        }

        $string = trim((string) $raw);

        if ($string === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $string);

        if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $string) {
            $errors[$errorKey] = 'Date must use YYYY-MM-DD format.';
            return null;
        }

        return $date->format('Y-m-d');
    }

    /** @param array<string,string> $errors */
    private function normalizeSelect(ContentTypeField $field, mixed $raw, array &$errors, string $errorKey): ?string
    {
        $value = $this->normalizeString($raw);

        if ($value === null) {
            return null;
        }

        $options = $field->settings()['options'] ?? null;

        if (!is_array($options) || $options === []) {
            return $value;
        }

        $normalizedOptions = array_values(array_map(static fn (mixed $option): string => trim((string) $option), $options));

        if (!in_array($value, $normalizedOptions, true)) {
            $errors[$errorKey] = sprintf('%s must be one of the configured options.', $field->label());
            return null;
        }

        return $value;
    }
}
