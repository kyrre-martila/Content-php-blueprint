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
        return $this->resolveSystemTemplate('404.php');
    }

    public function resolveSystemTemplate(string $template): string
    {
        $normalizedTemplate = trim($template, '/');

        return $this->templatesBasePath . '/system/' . $normalizedTemplate;
    }
}
