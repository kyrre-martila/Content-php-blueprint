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
use App\Http\Controller\RobotsController;
use App\Http\Controller\SearchController;
use App\Http\Controller\SitemapController;
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
        HomeController $homeController,
        HealthController $healthController,
        SearchController $searchController,
        AuthController $authController,
        DashboardController $dashboardController,
        PatternController $patternController,
        DevModeController $devModeController,
        CsrfMiddleware $csrf,
        RequireAuthMiddleware $requireAuth,
        ?InstallController $installController = null,
        ?ContentAdminController $contentAdminController = null,
        ?EditorModeController $editorModeController = null,
        ?ContentController $contentController = null,
        ?SitemapController $sitemapController = null,
        ?RobotsController $robotsController = null,
    ) {
        // Registration order is explicit so route priority is deterministic.

        /** System routes: core runtime endpoints and install flow. */
        (new SystemRouteRegistrar(
            homeController: $homeController,
            healthController: $healthController,
            searchController: $searchController,
            csrf: $csrf,
            installController: $installController,
            sitemapController: $sitemapController,
            robotsController: $robotsController
        ))->register($this);

        /** Auth routes: login/logout endpoints and system aliases. */
        (new AuthRouteRegistrar(
            authController: $authController,
            csrf: $csrf,
            requireAuth: $requireAuth
        ))->register($this);

        /** Admin routes: dashboard, patterns, and content management. */
        (new AdminRouteRegistrar(
            dashboardController: $dashboardController,
            patternController: $patternController,
            contentAdminController: $contentAdminController,
            csrf: $csrf,
            requireAuth: $requireAuth
        ))->register($this);

        /** Dev mode routes: privileged source-editing and export surfaces. */
        (new DevModeRouteRegistrar(
            devModeController: $devModeController,
            csrf: $csrf,
            requireAuth: $requireAuth
        ))->register($this);

        /** Editor mode routes: authenticated inline editing controls. */
        (new EditorModeRouteRegistrar(
            editorModeController: $editorModeController,
            csrf: $csrf,
            requireAuth: $requireAuth
        ))->register($this);

        /** Public content routes: catch-all content resolution, always last. */
        (new PublicContentRouteRegistrar(
            contentController: $contentController,
            csrf: $csrf
        ))->register($this);
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

    /**
     * @param callable(Request): Response $handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes[] = Route::create('GET', $path, $handler);
    }

    /**
     * @param callable(Request): Response $handler
     */
    public function post(string $path, callable $handler): void
    {
        $this->routes[] = Route::create('POST', $path, $handler);
    }
}
