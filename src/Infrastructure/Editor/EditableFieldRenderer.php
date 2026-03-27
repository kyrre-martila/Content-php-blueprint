<?php

declare(strict_types=1);

namespace App\Infrastructure\Editor;

final class EditableFieldRenderer
{
    /**
     * @param array<string, mixed> $meta
     */
    public function renderText(string $value, array $meta, bool $isEditorModeActive): string
    {
        return $this->render($value, $meta, $isEditorModeActive, false);
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function renderTextarea(string $value, array $meta, bool $isEditorModeActive): string
    {
        return $this->render($value, $meta, $isEditorModeActive, true);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function render(string $value, array $meta, bool $isEditorModeActive, bool $preserveLineBreaks): string
    {
        $escapedValue = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $renderedValue = $preserveLineBreaks ? nl2br($escapedValue, false) : $escapedValue;

        if (!$isEditorModeActive) {
            return $renderedValue;
        }

        $attributes = $this->buildAttributes($meta);

        if ($attributes === '') {
            return $renderedValue;
        }

        return sprintf('<span %s>%s</span>', $attributes, $renderedValue);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function buildAttributes(array $meta): string
    {
        $required = [
            'data-edit-type',
            'data-edit-field',
            'data-content-id',
        ];

        foreach ($required as $requiredAttribute) {
            $value = $this->attributeValue($meta, $requiredAttribute);

            if ($value === null) {
                return '';
            }
        }

        $attributes = ['data-editable="true"'];
        $allowed = [
            'data-edit-type',
            'data-edit-field',
            'data-content-id',
            'data-block-index',
        ];

        foreach ($allowed as $key) {
            $value = $this->attributeValue($meta, $key);

            if ($value === null) {
                continue;
            }

            $attributes[] = sprintf(
                '%s="%s"',
                $key,
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        }

        return implode(' ', $attributes);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function attributeValue(array $meta, string $key): ?string
    {
        $value = $meta[$key] ?? null;

        if (!is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        return $string;
    }
}
