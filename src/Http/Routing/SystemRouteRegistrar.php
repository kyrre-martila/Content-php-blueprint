<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Http\Controller\HealthController;
use App\Http\Controller\HomeController;
use App\Http\Controller\InstallController;
use App\Http\Controller\RobotsController;
use App\Http\Controller\SearchController;
use App\Http\Controller\SitemapController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Request;
use App\Http\Response;

final class SystemRouteRegistrar
{
    public function __construct(
        private readonly HomeController $homeController,
        private readonly HealthController $healthController,
        private readonly SearchController $searchController,
        private readonly CsrfMiddleware $csrf,
        private readonly ?InstallController $installController = null,
        private readonly ?SitemapController $sitemapController = null,
        private readonly ?RobotsController $robotsController = null,
    ) {
    }

    public function register(RouteRegistry $routeRegistry): void
    {
        $routeRegistry->get('/', [$this->homeController, 'index']);
        $routeRegistry->get('/health', [$this->healthController, 'show']);

        $routeRegistry->get('/search', fn (Request $request): Response => ($this->csrf)(
            $request,
            [$this->searchController, 'index']
        ));

        if ($this->sitemapController !== null) {
            $routeRegistry->get('/sitemap.xml', [$this->sitemapController, 'index']);
        }

        if ($this->robotsController !== null) {
            // Explicitly register robots as a system route so runtime output wins over slug/catch-all resolution.
            $routeRegistry->get('/robots.txt', [$this->robotsController, 'index']);
        }

        if ($this->installController !== null) {
            $routeRegistry->get('/install', fn (Request $request): Response => ($this->csrf)(
                $request,
                [$this->installController, 'show']
            ));
            $routeRegistry->post('/install', fn (Request $request): Response => ($this->csrf)(
                $request,
                [$this->installController, 'install']
            ));

            return;
        }

        $routeRegistry->get('/install', static fn (): Response => Response::redirect('/'));
    }
}
