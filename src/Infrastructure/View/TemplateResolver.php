<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use App\Domain\Content\CategoryGroup;
use App\Domain\Content\ContentType;

final class TemplateResolver
{
    public function __construct(private readonly string $templatesBasePath)
    {
    }

    public function resolveContentTemplate(ContentType $type): string
    {
        // Content route resolution order:
        // 1) templates/content/{content_type}.php
        // 2) templates/index.php fallback
        $contentTypeTemplate = $this->resolveTemplatePath('content/' . $type->name() . '.php');

        if ($this->templateExists($contentTypeTemplate)) {
            return $contentTypeTemplate;
        }

        return $this->resolveTemplatePath('index.php');
    }

    public function resolveCollectionTemplate(ContentType $type): string
    {
        // Collection route resolution order:
        // 1) templates/collections/{content_type}.php
        // 2) templates/system/404.php fallback
        $collectionTemplate = $this->resolveTemplatePath('collections/' . $type->name() . '.php');

        if ($this->templateExists($collectionTemplate)) {
            return $collectionTemplate;
        }

        return $this->resolveTemplatePath('system/404.php');
    }

    public function resolveCategoryCollectionTemplate(CategoryGroup $group, ?ContentType $type = null): string
    {
        // Category collection route resolution order:
        // 1) templates/categories/{category_group_slug}.php
        // 2) templates/collections/{content_type}.php (when content type is applicable)
        // 3) templates/system/404.php fallback
        $groupTemplate = $this->resolveTemplatePath('categories/' . $group->slug()->value() . '.php');

        if ($this->templateExists($groupTemplate)) {
            return $groupTemplate;
        }

        if ($type !== null) {
            $collectionTemplate = $this->resolveTemplatePath('collections/' . $type->name() . '.php');

            if ($this->templateExists($collectionTemplate)) {
                return $collectionTemplate;
            }
        }

        return $this->resolveTemplatePath('system/404.php');
    }

    public function resolveNotFound(): string
    {
        return $this->resolveSystemTemplate('404');
    }

    public function templateExists(string $path): bool
    {
        $normalizedPath = ltrim(trim($path), '/');

        if ($normalizedPath === '') {
            return false;
        }

        if (str_starts_with($normalizedPath, 'templates/')) {
            $normalizedPath = substr($normalizedPath, strlen('templates/'));
        }

        return is_file($this->resolveTemplatePath($normalizedPath));
    }

    /**
     * @param list<string> $directories
     * @return array<string, bool>
     */
    public function templateExistsMap(array $directories = ['content', 'collections', 'categories']): array
    {
        $map = [];

        foreach ($directories as $directory) {
            $normalizedDirectory = trim($directory, '/');

            if ($normalizedDirectory === '') {
                continue;
            }

            $pattern = $this->resolveTemplatePath($normalizedDirectory . '/*.php');
            $files = glob($pattern);

            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                $path = $normalizedDirectory . '/' . basename($file);
                $map[$path] = true;
            }
        }

        return $map;
    }

    public function resolveSystemTemplate(string $route): string
    {
        $normalizedRoute = trim($route);
        $normalizedRoute = trim($normalizedRoute, '/');
        $normalizedRoute = str_ends_with($normalizedRoute, '.php') ? substr($normalizedRoute, 0, -4) : $normalizedRoute;

        // System route resolution order:
        // 1) templates/system/{route}.php
        // 2) templates/system/404.php fallback
        $systemTemplate = $this->resolveTemplatePath('system/' . $normalizedRoute . '.php');

        if ($this->templateExists($systemTemplate)) {
            return $systemTemplate;
        }

        return $this->resolveTemplatePath('system/404.php');
    }

    private function resolveTemplatePath(string $templatePath): string
    {
        $normalizedTemplatePath = ltrim(trim($templatePath), '/');

        return rtrim($this->absolutePath($this->templatesBasePath), '/\\') . '/' . $normalizedTemplatePath;
    }

    private function absolutePath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));

        if ($normalized === '') {
            return getcwd() ?: '/';
        }

        if (str_starts_with($normalized, '/')) {
            return rtrim($normalized, '/');
        }

        $cwd = getcwd();

        if (!is_string($cwd) || $cwd === '') {
            return $normalized;
        }

        return rtrim(str_replace('\\', '/', $cwd), '/') . '/' . ltrim($normalized, '/');
    }
}
