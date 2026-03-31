<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use App\Domain\Content\CategoryGroup;
use App\Domain\Content\ContentType;

final class TemplateResolver
{
    public function __construct(
        private readonly string $templatesBasePath,
        private readonly TemplatePathMap $templatePathMap
    ) {
    }

    public function resolveContentTemplate(ContentType $type): string
    {
        // Path generation is delegated to TemplatePathMap.
        // Resolver remains responsible for existence checks + fallback resolution.
        $contentTypeTemplate = $this->absoluteTemplatePath($this->templatePathMap->contentTemplate($type));

        if ($this->templateExists($contentTypeTemplate)) {
            return $contentTypeTemplate;
        }

        return $this->absoluteTemplatePath($this->templatePathMap->indexFallbackTemplate());
    }

    public function resolveCollectionTemplate(ContentType $type): string
    {
        $collectionTemplate = $this->absoluteTemplatePath($this->templatePathMap->collectionTemplate($type));

        if ($this->templateExists($collectionTemplate)) {
            return $collectionTemplate;
        }

        return $this->absoluteTemplatePath($this->templatePathMap->systemTemplate('404'));
    }

    public function resolveCategoryCollectionTemplate(CategoryGroup $group): string
    {
        $groupTemplate = $this->absoluteTemplatePath($this->templatePathMap->categoryCollectionTemplate($group));

        if ($this->templateExists($groupTemplate)) {
            return $groupTemplate;
        }

        return $this->absoluteTemplatePath($this->templatePathMap->systemTemplate('404'));
    }

    public function resolveNotFound(): string
    {
        return $this->resolveSystemTemplate('404');
    }

    public function templateExists(string $path): bool
    {
        $candidatePath = trim($path);

        if ($candidatePath === '') {
            return false;
        }

        if (is_file($candidatePath)) {
            return true;
        }

        $normalizedPath = ltrim($candidatePath, '/');

        if (str_starts_with($normalizedPath, 'templates/')) {
            $normalizedPath = substr($normalizedPath, strlen('templates/'));
        }

        return is_file($this->absoluteTemplatePath($normalizedPath));
    }

    /**
     * @param list<string> $directories
     * @return array<string, bool>
     */
    public function templateExistsMap(array $directories = ['content', 'collections', 'collections/categories']): array
    {
        $map = [];

        foreach ($directories as $directory) {
            $normalizedDirectory = trim($directory, '/');

            if ($normalizedDirectory === '') {
                continue;
            }

            $pattern = $this->absoluteTemplatePath($normalizedDirectory . '/*.php');
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
        $systemTemplate = $this->absoluteTemplatePath($this->templatePathMap->systemTemplate($route));

        if ($this->templateExists($systemTemplate)) {
            return $systemTemplate;
        }

        return $this->absoluteTemplatePath($this->templatePathMap->systemTemplate('404'));
    }

    private function absoluteTemplatePath(string $templatePath): string
    {
        $normalizedTemplatePath = ltrim(trim($templatePath), '/');
        if (str_starts_with($normalizedTemplatePath, 'templates/')) {
            $normalizedTemplatePath = substr($normalizedTemplatePath, strlen('templates/'));
        }

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
