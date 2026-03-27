<?php

declare(strict_types=1);

namespace App\Infrastructure\Editor;

use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Infrastructure\Pattern\PatternRegistry;

final class EditableFieldValidator
{
    public function __construct(
        private readonly EditorMode $editorMode,
        private readonly ContentItemRepositoryInterface $contentItems,
        private readonly PatternRegistry $patternRegistry
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, int|string>
     */
    public function validate(array $payload): array
    {
        if (!$this->editorMode->isActive()) {
            throw new EditableFieldValidationException('Editor mode is not active.');
        }

        $type = $this->requiredString($payload, 'type');
        $contentId = $this->requiredInt($payload, 'content_id');
        $field = $this->requiredString($payload, 'field');
        $value = $this->requiredString($payload, 'value', allowEmpty: true);

        $contentItem = $this->contentItems->findById($contentId);

        if ($contentItem === null) {
            throw new EditableFieldValidationException('Content item was not found.');
        }

        if ($type === 'content_item') {
            if (!in_array($field, ['title', 'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex'], true)) {
                throw new EditableFieldValidationException('Unsupported content item field.');
            }

            return [
                'type' => $type,
                'content_id' => $contentId,
                'field' => $field,
                'value' => $value,
            ];
        }

        if ($type !== 'pattern_block') {
            throw new EditableFieldValidationException('Unsupported editable type.');
        }

        $blockIndex = $this->requiredInt($payload, 'block_index');
        $patternBlocks = $contentItem->patternBlocks();
        $block = $patternBlocks[$blockIndex] ?? null;

        if (!is_array($block)) {
            throw new EditableFieldValidationException('Pattern block index is invalid.');
        }

        $patternSlug = $block['pattern'] ?? null;

        if (!is_string($patternSlug) || trim($patternSlug) === '') {
            throw new EditableFieldValidationException('Pattern block metadata is invalid.');
        }

        $pattern = $this->patternRegistry->get($patternSlug);

        if ($pattern === null) {
            throw new EditableFieldValidationException('Pattern block type is not registered.');
        }

        $fieldMetadata = $this->resolvePatternField($pattern->fields(), $field);

        if ($fieldMetadata === null) {
            throw new EditableFieldValidationException('Pattern field is not editable.');
        }

        if (!in_array($fieldMetadata['type'], ['text', 'textarea'], true)) {
            throw new EditableFieldValidationException('Pattern field type is not supported for inline editing.');
        }

        return [
            'type' => $type,
            'content_id' => $contentId,
            'block_index' => $blockIndex,
            'field' => $field,
            'value' => $value,
        ];
    }

    /**
     * @param list<array{name: string, type: string}> $fields
     * @return array{name: string, type: string}|null
     */
    private function resolvePatternField(array $fields, string $fieldName): ?array
    {
        foreach ($fields as $field) {
            if (($field['name'] ?? '') !== $fieldName) {
                continue;
            }

            return $field;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredInt(array $payload, string $key): int
    {
        $rawValue = $payload[$key] ?? null;

        if (!is_scalar($rawValue) || !ctype_digit((string) $rawValue)) {
            throw new EditableFieldValidationException(sprintf('Field "%s" must be a positive integer.', $key));
        }

        return (int) $rawValue;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredString(array $payload, string $key, bool $allowEmpty = false): string
    {
        $rawValue = $payload[$key] ?? null;

        if (!is_scalar($rawValue)) {
            throw new EditableFieldValidationException(sprintf('Field "%s" must be a string.', $key));
        }

        $value = (string) $rawValue;

        if (!$allowEmpty && trim($value) === '') {
            throw new EditableFieldValidationException(sprintf('Field "%s" is required.', $key));
        }

        return $value;
    }
}
