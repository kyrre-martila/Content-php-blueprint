<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Admin\Controller\DevModeController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Request;
use App\Http\Response;

final class DevModeRouteRegistrar
{
    public function __construct(
        private readonly DevModeController $devModeController,
        private readonly CsrfMiddleware $csrf,
        private readonly RequireAuthMiddleware $requireAuth,
    ) {
    }

    public function register(RouteRegistry $routeRegistry): void
    {
        $enableHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'enable']
            )
        );
        $disableHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'disable']
            )
        );
        $indexHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'index']
            )
        );
        $editHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'edit']
            )
        );
        $updateHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'update']
            )
        );
        $exportHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'exportSnapshot']
            )
        );

        $routeRegistry->post('/admin/dev-mode/enable', $enableHandler);
        $routeRegistry->post('/admin/dev-mode/disable', $disableHandler);
        $routeRegistry->get('/admin/dev-mode', $indexHandler);
        $routeRegistry->get('/admin/dev-mode/edit', $editHandler);
        $routeRegistry->post('/admin/dev-mode/edit', $updateHandler);
        $routeRegistry->post('/admin/dev/export', $exportHandler);

        // System aliases
        $routeRegistry->post('/dev/enable', $enableHandler);
        $routeRegistry->post('/dev/disable', $disableHandler);
        $routeRegistry->get('/dev', $indexHandler);
        $routeRegistry->get('/dev/edit', $editHandler);
        $routeRegistry->post('/dev/edit', $updateHandler);
    }
}
