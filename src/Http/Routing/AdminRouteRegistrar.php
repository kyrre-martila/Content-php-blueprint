<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Admin\Controller\ContentAdminController;
use App\Admin\Controller\CategoryAdminController;
use App\Admin\Controller\DashboardController;
use App\Admin\Controller\ContentTypeAdminController;
use App\Admin\Controller\TemplateAdminController;
use App\Admin\Controller\PatternController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\MiddlewareStackBuilder;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Middleware\RequireRoleMiddleware;

final class AdminRouteRegistrar
{
    public function __construct(
        private readonly DashboardController $dashboardController,
        private readonly PatternController $patternController,
        private readonly ?ContentAdminController $contentAdminController,
        private readonly ?TemplateAdminController $templateAdminController,
        private readonly ?ContentTypeAdminController $contentTypeAdminController,
        private readonly ?CategoryAdminController $categoryAdminController,
        private readonly CsrfMiddleware $csrf,
        private readonly RequireAuthMiddleware $requireAuth,
        private readonly RequireRoleMiddleware $requireRole,
        private readonly MiddlewareStackBuilder $middlewareStackBuilder,
    ) {
    }

    public function register(RouteRegistry $routeRegistry): void
    {
        $middleware = [$this->csrf, $this->requireAuth, $this->requireRole];

        $routeRegistry->get('/admin', $this->middlewareStackBuilder->wrap([
            $this->dashboardController,
            'index',
        ], $middleware));

        $routeRegistry->get('/admin/patterns', $this->middlewareStackBuilder->wrap([
            $this->patternController,
            'index',
        ], $middleware));

        if ($this->templateAdminController !== null) {
            $routeRegistry->get('/admin/templates', $this->middlewareStackBuilder->wrap([
                $this->templateAdminController,
                'index',
            ], $middleware));
            $routeRegistry->get('/admin/system-templates', $this->middlewareStackBuilder->wrap([
                $this->templateAdminController,
                'systemIndex',
            ], $middleware));
            $routeRegistry->get('/admin/templates/edit', $this->middlewareStackBuilder->wrap([
                $this->templateAdminController,
                'edit',
            ], $middleware));
            $routeRegistry->post('/admin/templates/edit', $this->middlewareStackBuilder->wrap([
                $this->templateAdminController,
                'update',
            ], $middleware));
        }

        if ($this->contentTypeAdminController !== null) {
            $routeRegistry->get('/admin/content-types', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'index',
            ], $middleware));
            $routeRegistry->get('/admin/content-types/create', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'create',
            ], $middleware));
            $routeRegistry->post('/admin/content-types/create', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'store',
            ], $middleware));
            $routeRegistry->get('/admin/content-types/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'edit',
            ], $middleware));
            $routeRegistry->post('/admin/content-types/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'update',
            ], $middleware));
            $routeRegistry->delete('/admin/content-types/{slug}', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'destroy',
            ], $middleware));
            $routeRegistry->post('/admin/content-types/{slug}', $this->middlewareStackBuilder->wrap([
                $this->contentTypeAdminController,
                'destroy',
            ], $middleware));
        }

        if ($this->categoryAdminController !== null) {
            $routeRegistry->get('/admin/categories', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'index',
            ], $middleware));
            $routeRegistry->post('/admin/categories/groups/create', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'storeGroup',
            ], $middleware));
            $routeRegistry->post('/admin/categories/groups/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'updateGroup',
            ], $middleware));
            $routeRegistry->post('/admin/categories/groups/{id}', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'destroyGroup',
            ], $middleware));
            $routeRegistry->delete('/admin/categories/groups/{id}', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'destroyGroup',
            ], $middleware));
            $routeRegistry->post('/admin/categories/create', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'storeCategory',
            ], $middleware));
            $routeRegistry->post('/admin/categories/{id}/edit', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'updateCategory',
            ], $middleware));
            $routeRegistry->post('/admin/categories/{id}', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'destroyCategory',
            ], $middleware));
            $routeRegistry->delete('/admin/categories/{id}', $this->middlewareStackBuilder->wrap([
                $this->categoryAdminController,
                'destroyCategory',
            ], $middleware));
        }

        if ($this->contentAdminController === null) {
            return;
        }

        $routeRegistry->get('/admin/content', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'index',
        ], $middleware));
        $routeRegistry->get('/admin/content/create', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'create',
        ], $middleware));
        $routeRegistry->post('/admin/content/create', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'store',
        ], $middleware));
        $routeRegistry->get('/admin/content/{id}/edit', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'edit',
        ], $middleware));
        $routeRegistry->post('/admin/content/{id}/edit', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'update',
        ], $middleware));
        $routeRegistry->delete('/admin/content/{id}', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'destroy',
        ], $middleware));
        $routeRegistry->post('/admin/content/{id}', $this->middlewareStackBuilder->wrap([
            $this->contentAdminController,
            'destroy',
        ], $middleware));
    }
}
