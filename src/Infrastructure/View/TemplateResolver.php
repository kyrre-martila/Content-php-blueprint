<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use App\Domain\Content\ContentItem;

final class TemplateResolver
{
    public function __construct(private readonly string $templatesBasePath)
    {
    }

    public function resolveContentTemplate(ContentItem $contentItem): string
    {
        $templateCandidates = [
            $contentItem->templateOverride(),
            $contentItem->type()->defaultTemplate(),
            'content/default.php',
            'index.php',
        ];

        foreach ($templateCandidates as $templateCandidate) {
            if (!is_string($templateCandidate) || trim($templateCandidate) === '') {
                continue;
            }

            $resolvedPath = $this->resolveTemplatePath($templateCandidate);

            if (is_file($resolvedPath)) {
                return $resolvedPath;
            }
        }

        return $this->resolveTemplatePath('index.php');
    }

    public function resolveNotFound(): string
    {
        return $this->resolveSystemTemplate('404');
    }

    public function resolveSystemTemplate(string $name): string
    {
        $normalizedName = trim($name);
        $normalizedName = trim($normalizedName, '/');
        $normalizedName = str_ends_with($normalizedName, '.php') ? substr($normalizedName, 0, -4) : $normalizedName;

        $systemTemplate = $this->resolveTemplatePath('system/' . $normalizedName . '.php');

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
