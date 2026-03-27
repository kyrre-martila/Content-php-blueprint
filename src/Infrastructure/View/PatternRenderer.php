<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use App\Infrastructure\Editor\EditableFieldRenderer;
use App\Infrastructure\Pattern\PatternDataValidator;
use App\Infrastructure\Pattern\PatternRegistry;
use InvalidArgumentException;
use RuntimeException;

final class PatternRenderer
{
    public function __construct(
        private readonly PatternRegistry $registry,
        private readonly PatternDataValidator $dataValidator,
        private readonly ?EditableFieldRenderer $editableFieldRenderer = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $key, array $data = []): string
    {
        $pattern = $this->registry->get($key);

        if ($pattern === null) {
            return '';
        }

        $viewPath = $this->registry->viewPathFor($key);

        if (!is_string($viewPath) || !is_file($viewPath)) {
            return '';
        }

        try {
            $fields = $this->dataValidator->validate($pattern, $this->fieldInput($data));
        } catch (InvalidArgumentException) {
            return '';
        }

        $editor = $this->editorInput($data['_editor'] ?? null);

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

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function fieldInput(array $data): array
    {
        unset($data['_editor']);

        return $data;
    }

    /**
     * @return array{content_id: string, block_index: string, active: bool}
     */
    private function editorInput(mixed $editor): array
    {
        if (!is_array($editor)) {
            return [
                'content_id' => '',
                'block_index' => '',
                'active' => false,
            ];
        }

        return [
            'content_id' => is_scalar($editor['content_id'] ?? null) ? (string) $editor['content_id'] : '',
            'block_index' => is_scalar($editor['block_index'] ?? null) ? (string) $editor['block_index'] : '',
            'active' => ($editor['active'] ?? false) === true,
        ];
    }
}
