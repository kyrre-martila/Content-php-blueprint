<?php

declare(strict_types=1);

namespace App\Infrastructure\Pattern;

final class PatternRegistry
{
    /** @var array<string, array{name: string, slug: string, description: string, fields: array<int, array{name: string, type: string}>, view_path: string}> */
    private array $patterns = [];

    public function __construct(private readonly string $patternsBasePath)
    {
        $this->load();
    }

    /**
     * @return array<string, array{name: string, slug: string, description: string, fields: array<int, array{name: string, type: string}>, view_path: string}>
     */
    public function all(): array
    {
        return $this->patterns;
    }

    /**
     * @return array{name: string, slug: string, description: string, fields: array<int, array{name: string, type: string}>, view_path: string}|null
     */
    public function get(string $slug): array|null
    {
        return $this->patterns[$slug] ?? null;
    }

    private function load(): void
    {
        if (!is_dir($this->patternsBasePath)) {
            return;
        }

        $directories = glob($this->patternsBasePath . '/*', GLOB_ONLYDIR);

        if ($directories === false) {
            return;
        }

        foreach ($directories as $directory) {
            $pattern = $this->loadPattern($directory);

            if ($pattern === null) {
                continue;
            }

            $this->patterns[$pattern['slug']] = $pattern;
        }
    }

    /**
     * @return array{name: string, slug: string, description: string, fields: array<int, array{name: string, type: string}>, view_path: string}|null
     */
    private function loadPattern(string $directory): ?array
    {
        $metadataPath = $directory . '/pattern.json';
        $viewPath = $directory . '/pattern.php';

        if (!is_file($metadataPath) || !is_file($viewPath)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($metadataPath), true);

        if (!is_array($decoded)) {
            return null;
        }

        $validated = $this->validateMetadata($decoded);

        if ($validated === null) {
            return null;
        }

        return [...$validated, 'view_path' => $viewPath];
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array{name: string, slug: string, description: string, fields: array<int, array{name: string, type: string}>}|null
     */
    private function validateMetadata(array $metadata): ?array
    {
        $name = $this->nonEmptyString($metadata['name'] ?? null);
        $slug = $this->nonEmptyString($metadata['slug'] ?? null);
        $description = $this->nonEmptyString($metadata['description'] ?? null);
        $fields = $metadata['fields'] ?? null;

        if ($name === null || $slug === null || $description === null || !is_array($fields)) {
            return null;
        }

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return null;
        }

        $validatedFields = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                return null;
            }

            $fieldName = $this->nonEmptyString($field['name'] ?? null);
            $fieldType = $this->nonEmptyString($field['type'] ?? null);

            if ($fieldName === null || $fieldType === null) {
                return null;
            }

            if (!in_array($fieldType, ['text', 'textarea', 'image'], true)) {
                return null;
            }

            $validatedFields[] = [
                'name' => $fieldName,
                'type' => $fieldType,
            ];
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'fields' => $validatedFields,
        ];
    }

    private function nonEmptyString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }
}
