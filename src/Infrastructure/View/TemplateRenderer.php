<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use App\Infrastructure\Editor\EditableFieldRenderer;
use RuntimeException;

final class TemplateRenderer
{
    public function __construct(
        private readonly string $templatesBasePath,
        private readonly ?PatternRenderer $patternRenderer = null,
        private readonly ?EditableFieldRenderer $editableFieldRenderer = null,
        private readonly ?string $siteUrl = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $templatePath, array $data = []): string
    {
        $data = $this->withCanonicalMeta($data);
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
     * @return array<string, mixed>
     */
    private function withCanonicalMeta(array $data): array
    {
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $existingCanonical = $meta['canonical'] ?? null;

        if (is_string($existingCanonical) && trim($existingCanonical) !== '') {
            return [...$data, 'meta' => $meta];
        }

        $canonical = null;
        $contentItem = $data['contentItem'] ?? null;

        if (is_object($contentItem) && method_exists($contentItem, 'canonicalUrl')) {
            $canonicalUrl = $contentItem->canonicalUrl();

            if (is_string($canonicalUrl) && trim($canonicalUrl) !== '') {
                $canonical = trim($canonicalUrl);
            }
        }

        if ($canonical === null && is_object($contentItem) && method_exists($contentItem, 'slug')) {
            $slug = $contentItem->slug();

            if (is_object($slug) && method_exists($slug, 'value')) {
                $canonical = $this->absoluteCanonicalFromPath('/' . ltrim((string) $slug->value(), '/'), $data['request'] ?? null);
            }
        }

        if ($canonical === null) {
            return [...$data, 'meta' => $meta];
        }

        return [
            ...$data,
            'meta' => [
                ...$meta,
                'canonical' => $canonical,
            ],
        ];
    }

    private function absoluteCanonicalFromPath(string $path, mixed $request): string
    {
        $normalizedPath = '/' . ltrim($path, '/');

        if (is_string($this->siteUrl) && trim($this->siteUrl) !== '') {
            return rtrim(trim($this->siteUrl), '/') . $normalizedPath;
        }

        if (is_object($request) && method_exists($request, 'serverParams')) {
            $server = $request->serverParams();
            $host = $server['HTTP_HOST'] ?? null;

            if (is_string($host) && trim($host) !== '') {
                $scheme = $server['REQUEST_SCHEME'] ?? null;

                if (!is_string($scheme) || trim($scheme) === '') {
                    $scheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http';
                }

                return strtolower(trim((string) $scheme)) . '://' . trim($host) . $normalizedPath;
            }
        }

        return $normalizedPath;
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
            $inlineFieldRenderer = $__renderer->editableFieldRenderer ?? new EditableFieldRenderer();
            $e = static fn (string $value): string => htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $isEditorMode = static function () use ($__data): bool {
                return ($__data['editorModeActive'] ?? false) === true && ($__data['editorCanUse'] ?? false) === true;
            };

            $editableText = static function (string $value, array $meta = []) use ($inlineFieldRenderer, $isEditorMode): string {
                return $inlineFieldRenderer->renderText($value, $meta, $isEditorMode());
            };

            $editableTextarea = static function (string $value, array $meta = []) use ($inlineFieldRenderer, $isEditorMode): string {
                return $inlineFieldRenderer->renderTextarea($value, $meta, $isEditorMode());
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

}
