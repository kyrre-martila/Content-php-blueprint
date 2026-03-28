<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Admin\Controller\ContentAdminController;
use App\Admin\Controller\DashboardController;
use App\Admin\Controller\PatternController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Request;
use App\Http\Response;

final class AdminRouteRegistrar
{
    public function __construct(
        private readonly DashboardController $dashboardController,
        private readonly PatternController $patternController,
        private readonly ?ContentAdminController $contentAdminController,
        private readonly CsrfMiddleware $csrf,
        private readonly RequireAuthMiddleware $requireAuth,
    ) {
    }

    public function register(RouteRegistry $routeRegistry): void
    {
        $routeRegistry->get('/admin', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->dashboardController, 'index']
            )
        ));

        $routeRegistry->get('/admin/patterns', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->patternController, 'index']
            )
        ));

        if ($this->contentAdminController === null) {
            return;
        }

        $routeRegistry->get('/admin/content', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'index']
            )
        ));
        $routeRegistry->get('/admin/content/create', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'create']
            )
        ));
        $routeRegistry->post('/admin/content/create', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'store']
            )
        ));
        $routeRegistry->get('/admin/content/{id}/edit', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'edit']
            )
        ));
        $routeRegistry->post('/admin/content/{id}/edit', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'update']
            )
        ));
        $routeRegistry->delete('/admin/content/{id}', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'destroy']
            )
        ));
        $routeRegistry->post('/admin/content/{id}', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'destroy']
            )
        ));
    }
}
