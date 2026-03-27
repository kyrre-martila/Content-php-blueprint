<?php

declare(strict_types=1);

namespace App\Http;

use App\Infrastructure\Application\InstallState;
use App\Infrastructure\Auth\SessionManager;
use App\Http\Routing\RouteRegistry;

final class Kernel
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly RouteRegistry $routeRegistry,
        private readonly ?InstallState $installState = null,
        private readonly bool $installationRequired = false
    ) {
    }

    public function handle(Request $request): Response
    {
        if ($this->shouldRedirectToInstall($request)) {
            return Response::redirect('/install');
        }

        $this->session->start();

        $routeMatch = $this->routeRegistry->resolve($request);

        if ($routeMatch === null) {
            return Response::html('<h1>404 Not Found</h1>', 404);
        }

        return $routeMatch->run($request);
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
