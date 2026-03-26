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

        $viewPath = $pattern['view_path'];

        if (!is_file($viewPath)) {
            return '';
        }

        ob_start();

        $renderPattern = static function (string $__patternPath, array $__fields): void {
            $fields = $__fields;
            $e = static fn (string $value): string => htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            include $__patternPath;
        };

        $renderPattern($viewPath, $fields);

        $output = ob_get_clean();

        if ($output === false) {
            throw new RuntimeException('Pattern output buffering failed.');
        }

        return $output;
    }
}
