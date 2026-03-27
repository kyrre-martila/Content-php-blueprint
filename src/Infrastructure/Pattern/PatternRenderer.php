<?php

declare(strict_types=1);

namespace App\Infrastructure\Pattern;

use App\Infrastructure\Editor\EditableFieldRenderer;
use RuntimeException;

final class PatternRenderer
{
    public function __construct(
        private readonly PatternRegistry $registry,
        private readonly ?EditableFieldRenderer $editableFieldRenderer = null
    ) {
    }

    /**
     * Safety model:
     * - Only registered pattern files from the filesystem registry are renderable.
     * - Pattern templates receive only scalar, field-level data via $fields.
     * - No service container, request, or global application objects are passed.
     * - Pattern output is rendered through output buffering and returned as HTML string.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $key, array $data = []): string
    {
        $pattern = $this->registry->get($key);

        if ($pattern === null) {
            return '';
        }

        $fields = [];

        foreach ($pattern->fields() as $field) {
            $name = $field['name'];
            $value = $data[$name] ?? '';

            if (is_scalar($value)) {
                $fields[$name] = (string) $value;
                continue;
            }

            $fields[$name] = '';
        }

        $editor = [
            'content_id' => is_scalar($data['_editor']['content_id'] ?? null) ? (string) $data['_editor']['content_id'] : '',
            'block_index' => is_scalar($data['_editor']['block_index'] ?? null) ? (string) $data['_editor']['block_index'] : '',
            'active' => ($data['_editor']['active'] ?? false) === true,
        ];

        $viewPath = $this->registry->viewPathFor($key);

        if (!is_string($viewPath) || !is_file($viewPath)) {
            return '';
        }

        ob_start();

        $inlineFieldRenderer = $this->editableFieldRenderer ?? new EditableFieldRenderer();

        $renderPattern = static function (
            string $__patternPath,
            array $__fields,
            array $__editor,
            EditableFieldRenderer $__inlineFieldRenderer
        ): void {
            $fields = $__fields;
            $editor = $__editor;
            $e = static fn (string $value): string => htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            $editableText = static function (string $field, string $value) use ($editor, $__inlineFieldRenderer): string {
                return $__inlineFieldRenderer->renderText($value, [
                    'data-edit-type' => 'pattern_block',
                    'data-edit-field' => $field,
                    'data-content-id' => $editor['content_id'] ?? '',
                    'data-block-index' => $editor['block_index'] ?? '',
                ], ($editor['active'] ?? false) === true);
            };
            $editableTextarea = static function (string $field, string $value) use ($editor, $__inlineFieldRenderer): string {
                return $__inlineFieldRenderer->renderTextarea($value, [
                    'data-edit-type' => 'pattern_block',
                    'data-edit-field' => $field,
                    'data-content-id' => $editor['content_id'] ?? '',
                    'data-block-index' => $editor['block_index'] ?? '',
                ], ($editor['active'] ?? false) === true);
            };

            include $__patternPath;
        };

        $renderPattern($viewPath, $fields, $editor, $inlineFieldRenderer);

        $output = ob_get_clean();

        if ($output === false) {
            throw new RuntimeException('Pattern output buffering failed.');
        }

        return $output;
    }
}
