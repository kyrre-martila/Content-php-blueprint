<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Admin\Controller\DevModeController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\MiddlewareStackBuilder;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Middleware\RequireRoleMiddleware;

final class DevModeRouteRegistrar
{
    public function __construct(
        private readonly DevModeController $devModeController,
        private readonly CsrfMiddleware $csrf,
        private readonly RequireAuthMiddleware $requireAuth,
        private readonly RequireRoleMiddleware $requireRole,
        private readonly MiddlewareStackBuilder $middlewareStackBuilder,
    ) {
    }

    public function register(RouteRegistry $routeRegistry): void
    {
        $middleware = [$this->csrf, $this->requireAuth, $this->requireRole];

        $routeRegistry->post('/admin/dev-mode/enable', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'enable',
        ], $middleware));
        $routeRegistry->post('/admin/dev-mode/disable', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'disable',
        ], $middleware));
        $routeRegistry->get('/admin/dev-mode', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'index',
        ], $middleware));
        $routeRegistry->get('/admin/dev-mode/edit', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'edit',
        ], $middleware));
        $routeRegistry->post('/admin/dev-mode/edit', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'update',
        ], $middleware));
        $routeRegistry->post('/admin/dev/export', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'exportSnapshot',
        ], $middleware));

        // System aliases
        $routeRegistry->post('/dev/enable', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'enable',
        ], $middleware));
        $routeRegistry->post('/dev/disable', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'disable',
        ], $middleware));
        $routeRegistry->get('/dev', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'index',
        ], $middleware));
        $routeRegistry->get('/dev/edit', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'edit',
        ], $middleware));
        $routeRegistry->post('/dev/edit', $this->middlewareStackBuilder->wrap([
            $this->devModeController,
            'update',
        ], $middleware));
    }

}
