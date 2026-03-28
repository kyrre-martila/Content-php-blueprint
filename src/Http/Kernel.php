<?php

declare(strict_types=1);

namespace App\Http;

use App\Infrastructure\Application\InstallState;
use App\Infrastructure\Auth\SessionManager;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Routing\RouteRegistry;

final class Kernel
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly RouteRegistry $routeRegistry,
        private readonly SecurityHeadersMiddleware $securityHeaders,
        private readonly ?InstallState $installState = null,
        private readonly bool $installationRequired = false
    ) {
    }

    public function handle(Request $request): Response
    {
        return ($this->securityHeaders)($request, function (Request $incomingRequest): Response {
            if ($this->shouldRedirectToInstall($incomingRequest)) {
                return Response::redirect('/install');
            }

            $this->session->start();

            $routeMatch = $this->routeRegistry->resolve($incomingRequest);

            if ($routeMatch === null) {
                return Response::html('<h1>404 Not Found</h1>', 404);
            }

            return $routeMatch->run($incomingRequest);
        });
    }

    private function shouldRedirectToInstall(Request $request): bool
    {
        if (!$this->isSetupDependentAdminPath($request->path())) {
            return false;
        }

        if ($this->installationRequired) {
            return true;
        }

        if ($this->installState === null) {
            return false;
        }

        return !$this->installState->isInstalled();
    }

    private function isSetupDependentAdminPath(string $path): bool
    {
        return $path === '/admin' || str_starts_with($path, '/admin/');
    }
}
