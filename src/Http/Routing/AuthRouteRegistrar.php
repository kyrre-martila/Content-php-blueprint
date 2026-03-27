<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Admin\Controller\AuthController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Request;
use App\Http\Response;

final class AuthRouteRegistrar
{
    public function __construct(
        private readonly AuthController $authController,
        private readonly CsrfMiddleware $csrf,
        private readonly RequireAuthMiddleware $requireAuth,
    ) {
    }

    public function register(RouteRegistry $routeRegistry): void
    {
        $loginHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            [$this->authController, 'showLogin']
        );
        $loginPostHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            [$this->authController, 'login']
        );
        $logoutHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->authController, 'logout']
            )
        );

        $routeRegistry->get('/admin/login', $loginHandler);
        $routeRegistry->post('/admin/login', $loginPostHandler);
        $routeRegistry->post('/admin/logout', $logoutHandler);

        // System aliases
        $routeRegistry->get('/login', $loginHandler);
        $routeRegistry->post('/login', $loginPostHandler);
        $routeRegistry->post('/logout', $logoutHandler);
    }
}
