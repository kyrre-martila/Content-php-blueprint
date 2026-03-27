<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use App\Infrastructure\Pattern\PatternRenderer;
use RuntimeException;

final class TemplateRenderer
{
    public function __construct(
        private readonly string $templatesBasePath,
        private readonly ?PatternRenderer $patternRenderer = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $templatePath, array $data = []): string
    {
        $layout = null;
        $content = $this->renderFile($templatePath, $data, $layout);

        if ($layout === null) {
            return $content;
        }

        $layoutPath = $this->resolveLayoutPath($layout);
        $unusedLayout = null;

        return $this->renderFile($layoutPath, [...$data, 'content' => $content], $unusedLayout);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderPattern(string $slug, array $data = []): string
    {
        if ($this->patternRenderer === null) {
            return '';
        }

        return $this->patternRenderer->render($slug, $data);
    }

    private function resolveLayoutPath(string $layout): string
    {
        $normalizedLayout = ltrim($layout, '/');
        $fullPath = $this->templatesBasePath . '/' . $normalizedLayout;

        if (!is_file($fullPath)) {
            throw new RuntimeException(sprintf('Layout not found at path "%s".', $normalizedLayout));
        }

        return $fullPath;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $templatePath, array $data, ?string &$layout): string
    {
        if (!is_file($templatePath)) {
            throw new RuntimeException(sprintf('Template not found at path "%s".', $templatePath));
        }

        ob_start();

        $renderTemplate = static function (string $__templatePath, array $__data, ?string &$__layout, self $__renderer): void {
            extract($__data, EXTR_SKIP);

            $renderer = $__renderer;
            $e = static fn (string $value): string => htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $isEditorMode = static function () use ($__data): bool {
                return ($__data['editorModeActive'] ?? false) === true && ($__data['editorCanUse'] ?? false) === true;
            };

            $editableText = static function (string $value, array $meta = []) use ($e, $isEditorMode): string {
                if (!$isEditorMode()) {
                    return $e($value);
                }

                $attributes = self::buildEditableAttributes($meta);

                if ($attributes === '') {
                    return $e($value);
                }

                return '<span class="editor-editable" ' . $attributes . '>' . $e($value) . '</span>';
            };

            $editableTextarea = static function (string $value, array $meta = []) use ($e, $isEditorMode): string {
                if (!$isEditorMode()) {
                    return nl2br($e($value), false);
                }

                $attributes = self::buildEditableAttributes($meta);

                if ($attributes === '') {
                    return nl2br($e($value), false);
                }

                return '<span class="editor-editable" ' . $attributes . '>' . nl2br($e($value), false) . '</span>';
            };

            include $__templatePath;

            if (isset($layout) && is_string($layout) && trim($layout) !== '') {
                $__layout = $layout;
            }
        };

        $renderTemplate($templatePath, $data, $layout, $this);

        $output = ob_get_clean();

        if ($output === false) {
            throw new RuntimeException('Template output buffering failed.');
        }

        return $output;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private static function buildEditableAttributes(array $meta): string
    {
        $allowed = [
            'data-edit-type',
            'data-edit-field',
            'data-edit-id',
            'data-edit-block-index',
            'data-edit-content-id',
        ];

        $attributes = [];

        foreach ($allowed as $key) {
            $value = $meta[$key] ?? null;

            if (!is_scalar($value)) {
                continue;
            }

            $string = trim((string) $value);

            if ($string === '') {
                continue;
            }

            $attributes[] = $key . '="' . htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return implode(' ', $attributes);
    }
}
