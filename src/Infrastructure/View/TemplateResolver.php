<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

final class TemplateResolver
{
    public function __construct(private readonly string $templatesBasePath)
    {
    }

    public function resolveContentTemplate(): string
    {
        return $this->templatesBasePath . '/index.php';
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

        $systemTemplate = $this->templatesBasePath . '/system/' . $normalizedName . '.php';

        if (is_file($systemTemplate)) {
            return $systemTemplate;
        }

        return $this->templatesBasePath . '/system/404.php';
    }
}
