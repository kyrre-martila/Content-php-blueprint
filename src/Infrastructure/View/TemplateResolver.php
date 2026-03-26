<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

final class TemplateResolver
{
    public function __construct(private readonly string $templatesBasePath)
    {
    }

    public function resolveForSlug(string $slug): string
    {
        $normalizedSlug = $this->normalizeSlug($slug);

        $candidates = [
            $this->templatesBasePath . '/pages/' . $normalizedSlug . '.php',
            $this->templatesBasePath . '/pages/content.php',
            $this->templatesBasePath . '/default.php',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $this->templatesBasePath . '/default.php';
    }

    private function normalizeSlug(string $slug): string
    {
        $normalizedSlug = trim(strtolower($slug));
        $normalizedSlug = preg_replace('/[^a-z0-9-]+/', '-', $normalizedSlug) ?? $normalizedSlug;
        $normalizedSlug = preg_replace('/-{2,}/', '-', $normalizedSlug) ?? $normalizedSlug;

        return trim($normalizedSlug, '-');
    }
}
