<?php

declare(strict_types=1);

namespace App\Http\Routing;

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
use App\Http\Request;
use App\Http\Response;
use App\Http\Route;

final class RouteRegistry
{
    /**
     * @var list<Route>
     */
    private array $routes = [];

    public function __construct(
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
    ) {
        $this->registerSystemRoutes();
        $this->registerAuthRoutes();
        $this->registerAdminRoutes();
        $this->registerDevModeRoutes();
        $this->registerEditorRoutes();
    }

    public function resolve(Request $request): ?RouteMatch
    {
        foreach ($this->routes as $route) {
            $parameters = $route->match($request->method(), $request->path());

            if ($parameters === null) {
                continue;
            }

            return new RouteMatch($route, $parameters);
        }

        return null;
    }

    private function registerSystemRoutes(): void
    {
        $this->get('/', [$this->homeController, 'index']);
        $this->get('/health', [$this->healthController, 'show']);

        $this->get('/search', fn (Request $request): Response => ($this->csrf)(
            $request,
            [$this->searchController, 'index']
        ));

        if ($this->installController !== null) {
            $this->get('/install', fn (Request $request): Response => ($this->csrf)(
                $request,
                [$this->installController, 'show']
            ));
            $this->post('/install', fn (Request $request): Response => ($this->csrf)(
                $request,
                [$this->installController, 'install']
            ));

            return;
        }

        $this->get('/install', static fn (): Response => Response::redirect('/'));
    }

    private function registerAuthRoutes(): void
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

        $this->get('/admin/login', $loginHandler);
        $this->post('/admin/login', $loginPostHandler);
        $this->post('/admin/logout', $logoutHandler);

        // System aliases
        $this->get('/login', $loginHandler);
        $this->post('/login', $loginPostHandler);
        $this->post('/logout', $logoutHandler);
    }

    private function registerAdminRoutes(): void
    {
        $this->get('/admin', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->dashboardController, 'index']
            )
        ));

        $this->get('/admin/patterns', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->patternController, 'index']
            )
        ));

        if (
            $this->contentAdminController === null
            || $this->editorModeController === null
            || $this->contentController === null
        ) {
            return;
        }

        $this->get('/admin/content', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'index']
            )
        ));
        $this->get('/admin/content/create', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'create']
            )
        ));
        $this->post('/admin/content/create', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'store']
            )
        ));
        $this->get('/admin/content/{id}/edit', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'edit']
            )
        ));
        $this->post('/admin/content/{id}/edit', fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->contentAdminController, 'update']
            )
        ));

        $this->get('/{slug}', fn (Request $request): Response => ($this->csrf)(
            $request,
            [$this->contentController, 'show']
        ));
    }

    private function registerDevModeRoutes(): void
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

        $this->post('/admin/dev-mode/enable', $enableHandler);
        $this->post('/admin/dev-mode/disable', $disableHandler);
        $this->get('/admin/dev-mode', $indexHandler);
        $this->get('/admin/dev-mode/edit', $editHandler);
        $this->post('/admin/dev-mode/edit', $updateHandler);
        $this->post('/admin/dev/export', $exportHandler);

        // System aliases
        $this->post('/dev/enable', $enableHandler);
        $this->post('/dev/disable', $disableHandler);
        $this->get('/dev', $indexHandler);
        $this->get('/dev/edit', $editHandler);
        $this->post('/dev/edit', $updateHandler);
    }

    private function registerEditorRoutes(): void
    {
        if ($this->editorModeController === null) {
            return;
        }

        $enableHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->editorModeController, 'enable']
            )
        );
        $disableHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->editorModeController, 'disable']
            )
        );
        $saveFieldHandler = fn (Request $request): Response => ($this->requireAuth)(
            $request,
            [$this->editorModeController, 'saveField']
        );

        $this->post('/editor-mode/enable', $enableHandler);
        $this->post('/editor-mode/disable', $disableHandler);
        $this->post('/editor-mode/save-field', $saveFieldHandler);

        // System aliases
        $this->post('/editor/enable', $enableHandler);
        $this->post('/editor/disable', $disableHandler);
        $this->post('/editor/save-field', $saveFieldHandler);
    }

    /**
     * @param callable(Request): Response $handler
     */
    private function get(string $path, callable $handler): void
    {
        $this->routes[] = Route::create('GET', $path, $handler);
    }

    /**
     * @param callable(Request): Response $handler
     */
    private function post(string $path, callable $handler): void
    {
        $this->routes[] = Route::create('POST', $path, $handler);
    }
}
