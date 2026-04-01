<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\ContentTypeField;
use DateTimeImmutable;

final class ContentTypeFieldSchemaService
{
    /**
     * @param array<string,mixed> $post
     * @return list<array{
     *   name:string,
     *   label:string,
     *   field_type:string,
     *   is_required:bool,
     *   default_value:?string,
     *   placeholder:?string,
     *   options_text:?string,
     *   min_value:?string,
     *   max_value:?string,
     *   allowed_types_text:?string
     * }>
     */
    public function extractFromPost(array $post): array
    {
        $rawNames = is_array($post['field_name'] ?? null) ? $post['field_name'] : [];
        $rawLabels = is_array($post['field_label'] ?? null) ? $post['field_label'] : [];
        $rawTypes = is_array($post['field_type'] ?? null) ? $post['field_type'] : [];
        $rawRequired = is_array($post['field_required'] ?? null) ? $post['field_required'] : [];
        $rawDefaults = is_array($post['field_default_value'] ?? null) ? $post['field_default_value'] : [];
        $rawPlaceholders = is_array($post['field_placeholder'] ?? null) ? $post['field_placeholder'] : [];
        $rawOptions = is_array($post['field_options'] ?? null) ? $post['field_options'] : [];
        $rawMinValues = is_array($post['field_min'] ?? null) ? $post['field_min'] : [];
        $rawMaxValues = is_array($post['field_max'] ?? null) ? $post['field_max'] : [];
        $rawAllowedTypes = is_array($post['field_allowed_types'] ?? null) ? $post['field_allowed_types'] : [];

        $rowCount = max(
            count($rawNames),
            count($rawLabels),
            count($rawTypes),
            count($rawDefaults),
            count($rawPlaceholders),
            count($rawOptions),
            count($rawMinValues),
            count($rawMaxValues),
            count($rawAllowedTypes)
        );

        $rows = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $row = [
                'name' => $this->stringOrEmpty($rawNames[$index] ?? null),
                'label' => $this->stringOrEmpty($rawLabels[$index] ?? null),
                'field_type' => $this->stringOrEmpty($rawTypes[$index] ?? null),
                'is_required' => array_key_exists((string) $index, $rawRequired) || array_key_exists($index, $rawRequired),
                'default_value' => $this->nullableString($rawDefaults[$index] ?? null),
                'placeholder' => $this->nullableString($rawPlaceholders[$index] ?? null),
                'options_text' => $this->nullableString($rawOptions[$index] ?? null),
                'min_value' => $this->nullableString($rawMinValues[$index] ?? null),
                'max_value' => $this->nullableString($rawMaxValues[$index] ?? null),
                'allowed_types_text' => $this->nullableString($rawAllowedTypes[$index] ?? null),
            ];

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param list<array{name:string,label:string,field_type:string,is_required:bool,default_value:?string,placeholder:?string,options_text:?string,min_value:?string,max_value:?string,allowed_types_text:?string}> $rows
     * @return array<string,string>
     */
    public function validate(array $rows): array
    {
        $errors = [];
        $names = [];

        foreach ($rows as $index => $row) {
            if ($row['name'] === '') {
                $errors['fields.' . $index . '.name'] = 'Field name is required.';
            } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $row['name'])) {
                $errors['fields.' . $index . '.name'] = 'Field name must start with a letter and use lowercase letters, numbers, or underscores.';
            } elseif (in_array($row['name'], $names, true)) {
                $errors['fields.' . $index . '.name'] = sprintf('Field name "%s" is duplicated.', $row['name']);
            }

            $names[] = $row['name'];

            if ($row['label'] === '') {
                $errors['fields.' . $index . '.label'] = 'Field label is required.';
            }

            if (!ContentTypeField::isSupportedFieldType($row['field_type'])) {
                $errors['fields.' . $index . '.field_type'] = 'Field type is not supported.';
                continue;
            }

            $fieldErrors = $this->validateTypeSpecificSettings($row, $index);
            foreach ($fieldErrors as $key => $message) {
                $errors[$key] = $message;
            }
        }

        return $errors;
    }

    /**
     * @param list<array{name:string,label:string,field_type:string,is_required:bool,default_value:?string,placeholder:?string,options_text:?string,min_value:?string,max_value:?string,allowed_types_text:?string}> $rows
     * @return list<ContentTypeField>
     */
    public function buildFieldObjects(array $rows): array
    {
        $now = new DateTimeImmutable();
        $fields = [];

        foreach ($rows as $index => $row) {
            $fields[] = new ContentTypeField(
                id: null,
                contentTypeId: 1,
                name: $row['name'],
                label: $row['label'],
                fieldType: $row['field_type'],
                isRequired: $row['is_required'],
                defaultValue: $row['default_value'],
                settings: $this->buildSettings($row),
                sortOrder: $index,
                createdAt: $now,
                updatedAt: $now,
            );
        }

        return $fields;
    }

    /**
     * @param list<ContentTypeField> $fields
     * @return list<array{name:string,label:string,field_type:string,is_required:bool,default_value:?string,placeholder:?string,options_text:?string,min_value:?string,max_value:?string,allowed_types_text:?string}>
     */
    public function fieldsForForm(array $fields): array
    {
        $rows = [];

        foreach ($fields as $field) {
            $settings = $field->settings() ?? [];

            $rows[] = [
                'name' => $field->name(),
                'label' => $field->label(),
                'field_type' => $field->fieldType(),
                'is_required' => $field->isRequired(),
                'default_value' => $field->defaultValue(),
                'placeholder' => is_string($settings['placeholder'] ?? null) ? $settings['placeholder'] : null,
                'options_text' => is_array($settings['options'] ?? null) ? implode("\n", array_map('strval', $settings['options'])) : null,
                'min_value' => isset($settings['min']) ? (string) $settings['min'] : null,
                'max_value' => isset($settings['max']) ? (string) $settings['max'] : null,
                'allowed_types_text' => is_array($settings['allowed_types'] ?? null) ? implode(', ', array_map('strval', $settings['allowed_types'])) : null,
            ];
        }

        return $rows;
    }

    /** @param array{name:string,label:string,field_type:string,is_required:bool,default_value:?string,placeholder:?string,options_text:?string,min_value:?string,max_value:?string,allowed_types_text:?string} $row */
    private function isEmptyRow(array $row): bool
    {
        return $row['name'] === ''
            && $row['label'] === ''
            && $row['field_type'] === ''
            && $row['default_value'] === null
            && $row['placeholder'] === null
            && $row['options_text'] === null
            && $row['min_value'] === null
            && $row['max_value'] === null
            && $row['allowed_types_text'] === null;
    }

    private function stringOrEmpty(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param array{name:string,label:string,field_type:string,is_required:bool,default_value:?string,placeholder:?string,options_text:?string,min_value:?string,max_value:?string,allowed_types_text:?string} $row
     * @return array<string,mixed>|null
     */
    private function buildSettings(array $row): ?array
    {
        return match ($row['field_type']) {
            'select' => ['options' => $this->splitLines($row['options_text'])],
            'text', 'textarea', 'richtext' => $row['placeholder'] !== null ? ['placeholder' => $row['placeholder']] : null,
            'number' => $this->buildNumberSettings($row),
            'file', 'image' => $row['allowed_types_text'] !== null ? ['allowed_types' => $this->splitDelimitedValues($row['allowed_types_text'])] : null,
            default => null,
        };
    }

    /** @param array{name:string,label:string,field_type:string,is_required:bool,default_value:?string,placeholder:?string,options_text:?string,min_value:?string,max_value:?string,allowed_types_text:?string} $row */
    private function buildNumberSettings(array $row): ?array
    {
        $settings = [];

        if ($row['min_value'] !== null) {
            $settings['min'] = (float) $row['min_value'];
        }

        if ($row['max_value'] !== null) {
            $settings['max'] = (float) $row['max_value'];
        }

        return $settings === [] ? null : $settings;
    }

    /**
     * @param array{name:string,label:string,field_type:string,is_required:bool,default_value:?string,placeholder:?string,options_text:?string,min_value:?string,max_value:?string,allowed_types_text:?string} $row
     * @return array<string,string>
     */
    private function validateTypeSpecificSettings(array $row, int $index): array
    {
        $errors = [];

        if ($row['field_type'] === 'select') {
            $options = $this->splitLines($row['options_text']);
            if ($options === []) {
                $errors['fields.' . $index . '.options_text'] = 'Select fields require at least one option.';
            }
        }

        if (in_array($row['field_type'], ['text', 'textarea', 'richtext'], true)) {
            if ($row['min_value'] !== null || $row['max_value'] !== null || $row['options_text'] !== null || $row['allowed_types_text'] !== null) {
                $errors['fields.' . $index . '.settings'] = 'This field type only supports an optional placeholder setting.';
            }
        }

        if ($row['field_type'] === 'number') {
            if ($row['options_text'] !== null || $row['placeholder'] !== null || $row['allowed_types_text'] !== null) {
                $errors['fields.' . $index . '.settings'] = 'Number fields support only min and max settings.';
            }

            if ($row['min_value'] !== null && !is_numeric($row['min_value'])) {
                $errors['fields.' . $index . '.min_value'] = 'Min must be a valid number.';
            }

            if ($row['max_value'] !== null && !is_numeric($row['max_value'])) {
                $errors['fields.' . $index . '.max_value'] = 'Max must be a valid number.';
            }

            if (is_numeric((string) $row['min_value']) && is_numeric((string) $row['max_value']) && (float) $row['min_value'] > (float) $row['max_value']) {
                $errors['fields.' . $index . '.max_value'] = 'Max must be greater than or equal to min.';
            }
        }

        if (in_array($row['field_type'], ['file', 'image'], true)) {
            if ($row['placeholder'] !== null || $row['min_value'] !== null || $row['max_value'] !== null || $row['options_text'] !== null) {
                $errors['fields.' . $index . '.settings'] = 'File and image fields support only allowed extension or mime hints.';
            }
        }

        if (in_array($row['field_type'], ['boolean', 'date'], true)) {
            if ($row['placeholder'] !== null || $row['min_value'] !== null || $row['max_value'] !== null || $row['options_text'] !== null || $row['allowed_types_text'] !== null) {
                $errors['fields.' . $index . '.settings'] = 'This field type does not support additional settings in v1.';
            }
        }

        return $errors;
    }

    /** @return list<string> */
    private function splitLines(?string $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $values = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return array_values(array_filter(array_map(static fn (string $value): string => trim($value), $values), static fn (string $value): bool => $value !== ''));
    }

    /** @return list<string> */
    private function splitDelimitedValues(string $raw): array
    {
        $values = preg_split('/[,\r\n]+/', $raw) ?: [];

        return array_values(array_filter(array_map(static fn (string $value): string => trim($value), $values), static fn (string $value): bool => $value !== ''));
    }
}
