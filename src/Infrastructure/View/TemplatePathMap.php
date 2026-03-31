<?php

declare(strict_types=1);

namespace App\Infrastructure\View;

use App\Domain\Content\CategoryGroup;
use App\Domain\Content\ContentType;

/**
 * TemplatePathMap builds canonical template paths for known route/content scenarios.
 *
 * Separation of concerns:
 * - This class generates expected template paths only.
 * - TemplateResolver decides if paths exist and which fallback should be resolved.
 */
final class TemplatePathMap
{
    public function contentTemplate(ContentType $type): string
    {
        return sprintf('templates/content/%s.php', $type->name());
    }

    public function collectionTemplate(ContentType $type): string
    {
        return sprintf('templates/collections/%s.php', $type->name());
    }

    public function categoryCollectionTemplate(CategoryGroup $group): string
    {
        return sprintf('templates/collections/categories/%s.php', $group->slug()->value());
    }

    public function systemTemplate(string $route): string
    {
        $normalizedRoute = trim($route);
        $normalizedRoute = trim($normalizedRoute, '/');
        $normalizedRoute = preg_replace('#^templates/system/#', '', $normalizedRoute) ?? $normalizedRoute;
        $normalizedRoute = str_ends_with($normalizedRoute, '.php')
            ? substr($normalizedRoute, 0, -4)
            : $normalizedRoute;

        if ($normalizedRoute === '') {
            $normalizedRoute = '404';
        }

        return sprintf('templates/system/%s.php', $normalizedRoute);
    }

    public function indexFallbackTemplate(): string
    {
        return 'templates/index.php';
    }
}
