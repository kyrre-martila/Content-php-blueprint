<?php

declare(strict_types=1);

namespace App\Http;

use App\Admin\Controller\AuthController;
use App\Admin\Controller\ContentAdminController;
use App\Admin\Controller\DashboardController;
use App\Admin\Controller\DevModeController;
use App\Admin\Controller\EditorModeController;
use App\Admin\Controller\PatternController;
use App\Http\Controller\ContentController;
use App\Http\Controller\HealthController;
use App\Http\Controller\HomeController;
use App\Http\Controller\InstallController;
use App\Http\Controller\SearchController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Infrastructure\Application\InstallState;
use App\Infrastructure\Auth\SessionManager;

final class Kernel
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly HomeController $homeController,
        private readonly HealthController $healthController,
        private readonly SearchController $searchController,
        private readonly AuthController $authController,
        private readonly DashboardController $dashboardController,
        private readonly PatternController $patternController,
        private readonly DevModeController $devModeController,
        private readonly CsrfMiddleware $csrf,
        private readonly RequireAuthMiddleware $requireAuth,
        private readonly ?InstallController $installController = null,
        private readonly ?ContentAdminController $contentAdminController = null,
        private readonly ?EditorModeController $editorModeController = null,
        private readonly ?ContentController $contentController = null,
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

        return $this->buildRouter()->dispatch($request);
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

    private function buildRouter(): Router
    {
        $router = new Router();

        $router->get('/', [$this->homeController, 'index']);
        $router->get('/health', [$this->healthController, 'show']);

        $router->get('/search', fn (Request $request): Response => ($this->csrf)(
            $request,
            [$this->searchController, 'index']
        ));

        if ($this->installController !== null) {
            $router->get('/install', fn (Request $request): Response => ($this->csrf)(
                $request,
                [$this->installController, 'show']
            ));
            $router->post('/install', fn (Request $request): Response => ($this->csrf)(
                $request,
                [$this->installController, 'install']
            ));
        } else {
            $router->get('/install', static fn (Request $request): Response => Response::redirect('/'));
        }

        $router->get('/admin/login', fn (Request $request): Response => ($this->csrf)(
            $request,
            [$this->authController, 'showLogin']
        ));
        $router->post('/admin/login', fn (Request $request): Response => ($this->csrf)(
            $request,
            [$this->authController, 'login']
        ));
        $router->post('/admin/logout', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->authController, 'logout']
            )
        ));
        $router->get('/admin', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->dashboardController, 'index']
            )
        ));

        $router->get('/admin/patterns', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->patternController, 'index']
            )
        ));

        $router->post('/admin/dev-mode/enable', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'enable']
            )
        ));
        $router->post('/admin/dev-mode/disable', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'disable']
            )
        ));
        $router->get('/admin/dev-mode', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'index']
            )
        ));
        $router->get('/admin/dev-mode/edit', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'edit']
            )
        ));
        $router->post('/admin/dev-mode/edit', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->devModeController, 'update']
            )
        ));

        if (
            $this->contentAdminController !== null
            && $this->editorModeController !== null
            && $this->contentController !== null
        ) {
            $router->get('/admin/content', fn (Request $request): Response => ($this->csrf)(
                $request,
                fn (Request $csrfRequest): Response => ($this->requireAuth)(
                    $csrfRequest,
                    [$this->contentAdminController, 'index']
                )
            ));
            $router->get('/admin/content/create', fn (Request $request): Response => ($this->csrf)(
                $request,
                fn (Request $csrfRequest): Response => ($this->requireAuth)(
                    $csrfRequest,
                    [$this->contentAdminController, 'create']
                )
            ));
            $router->post('/admin/content/create', fn (Request $request): Response => ($this->csrf)(
                $request,
                fn (Request $csrfRequest): Response => ($this->requireAuth)(
                    $csrfRequest,
                    [$this->contentAdminController, 'store']
                )
            ));
            $router->get('/admin/content/{id}/edit', fn (Request $request): Response => ($this->csrf)(
                $request,
                fn (Request $csrfRequest): Response => ($this->requireAuth)(
                    $csrfRequest,
                    [$this->contentAdminController, 'edit']
                )
            ));
            $router->post('/admin/content/{id}/edit', fn (Request $request): Response => ($this->csrf)(
                $request,
                fn (Request $csrfRequest): Response => ($this->requireAuth)(
                    $csrfRequest,
                    [$this->contentAdminController, 'update']
                )
            ));
            $router->post('/editor-mode/enable', fn (Request $request): Response => ($this->csrf)(
                $request,
                fn (Request $csrfRequest): Response => ($this->requireAuth)(
                    $csrfRequest,
                    [$this->editorModeController, 'enable']
                )
            ));
            $router->post('/editor-mode/disable', fn (Request $request): Response => ($this->csrf)(
                $request,
                fn (Request $csrfRequest): Response => ($this->requireAuth)(
                    $csrfRequest,
                    [$this->editorModeController, 'disable']
                )
            ));
            $router->post('/editor-mode/save-field', fn (Request $request): Response => ($this->requireAuth)(
                $request,
                [$this->editorModeController, 'saveField']
            ));

            $router->get('/{slug}', fn (Request $request): Response => ($this->csrf)(
                $request,
                [$this->contentController, 'show']
            ));
        }

        return $router;
    }
}
