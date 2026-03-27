<?php

declare(strict_types=1);

namespace App\Infrastructure\Pattern;

use RuntimeException;

final class PatternRenderer
{
    public function __construct(private readonly PatternRegistry $registry)
    {
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
    public function render(string $slug, array $data = []): string
    {
        $pattern = $this->registry->get($slug);

        if ($pattern === null) {
            return '';
        }

        $fields = [];

        foreach ($pattern['fields'] as $field) {
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
        ];

        $viewPath = $pattern['view_path'];

        if (!is_file($viewPath)) {
            return '';
        }

        ob_start();

        $renderPattern = static function (string $__patternPath, array $__fields, array $__editor): void {
            $fields = $__fields;
            $editor = $__editor;
            $e = static fn (string $value): string => htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            $editableText = static function (string $field, string $value) use ($editor, $e): string {
                if (($editor['content_id'] ?? '') === '' || ($editor['block_index'] ?? '') === '') {
                    return $e($value);
                }

                return '<span class="editor-editable" data-edit-type="pattern_block" data-edit-field="'
                    . $e($field)
                    . '" data-edit-block-index="' . $e($editor['block_index'])
                    . '" data-edit-content-id="' . $e($editor['content_id'])
                    . '">' . $e($value) . '</span>';
            };
            $editableTextarea = static function (string $field, string $value) use ($editor, $e): string {
                if (($editor['content_id'] ?? '') === '' || ($editor['block_index'] ?? '') === '') {
                    return nl2br($e($value), false);
                }

                return '<span class="editor-editable" data-edit-type="pattern_block" data-edit-field="'
                    . $e($field)
                    . '" data-edit-block-index="' . $e($editor['block_index'])
                    . '" data-edit-content-id="' . $e($editor['content_id'])
                    . '">' . nl2br($e($value), false) . '</span>';
            };

            include $__patternPath;
        };

        $renderPattern($viewPath, $fields, $editor);

        $output = ob_get_clean();

        if ($output === false) {
            throw new RuntimeException('Pattern output buffering failed.');
        }

        return $output;
    }
}
