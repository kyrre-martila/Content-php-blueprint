<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

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

        if (is_file($contentTypeTemplate)) {
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

        if (is_file($collectionTemplate)) {
            return $collectionTemplate;
        }

        return $this->resolveTemplatePath('system/404.php');
    }

    public function resolveNotFound(): string
    {
        return $this->resolveSystemTemplate('404');
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

        if (is_file($systemTemplate)) {
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
