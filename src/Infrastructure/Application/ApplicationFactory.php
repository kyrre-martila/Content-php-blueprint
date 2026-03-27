<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Http\Kernel;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Routing\RouteRegistry;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Config\ConfigRepository;
use App\Infrastructure\Logging\Logger;

final class ApplicationFactory
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly ConfigRepository $config,
        private readonly Logger $logger
    ) {
    }

    public function createKernel(): Kernel
    {
        /** @var array<string, mixed> $sessionConfig */
        $sessionConfig = $this->config->get('app.session', []);

        $configuredAppUrl = $this->config->get('app.url');
        $siteUrl = is_string($configuredAppUrl) ? $configuredAppUrl : '';
        $configuredAppName = $this->config->get('app.name', 'Content PHP Blueprint');
        $siteName = is_string($configuredAppName) ? $configuredAppName : 'Content PHP Blueprint';
        $configuredAppEnvironment = $this->config->get('app.env', 'production');
        $appEnvironment = is_string($configuredAppEnvironment) ? $configuredAppEnvironment : 'production';

        $persistence = (new PersistenceFactory($this->config, $this->logger))->build();
        $views = (new ViewFactory($this->projectRoot, $siteUrl, $siteName))->build();

        $sessionManager = new SessionManager($sessionConfig);
        $csrf = new CsrfMiddleware($sessionManager);
        $authSession = new AuthSession($sessionManager);
        $requireAuth = new RequireAuthMiddleware($authSession);

        $editors = (new EditorFactory($this->projectRoot))->build(
            $authSession,
            $sessionManager,
            $persistence['contentItemRepository'],
            $views['patternRegistry']
        );

        $exporters = (new ExporterFactory($this->projectRoot))->build(
            $persistence['contentTypeRepository'],
            $persistence['contentItemRepository']
        );

        $controllers = (new ControllerFactory(
            $this->projectRoot,
            $appEnvironment,
            $siteUrl,
            $this->logger
        ))->build(
            $persistence['userRepository'],
            $persistence['contentItemRepository'],
            $persistence['contentTypeRepository'],
            $views['templateResolver'],
            $views['templateRenderer'],
            $views['patternRegistry'],
            $authSession,
            $sessionManager,
            $editors['editorMode'],
            $editors['devMode'],
            $exporters['compositionExporter'],
            $exporters['ocfExporter'],
            $exporters['ocfUnavailable'],
            $editors['devFileService'],
            $editors['devModeFiles'],
            $editors['devModeHistory'],
            $persistence['installState'],
            $persistence['installationRequired'],
            $persistence['migrationsTable'],
            $editors['editorContentService']
        );

        $routeRegistry = new RouteRegistry(
            homeController: $controllers['homeController'],
            healthController: $controllers['healthController'],
            searchController: $controllers['searchController'],
            authController: $controllers['authController'],
            dashboardController: $controllers['dashboardController'],
            patternController: $controllers['patternController'],
            devModeController: $controllers['devModeController'],
            csrf: $csrf,
            requireAuth: $requireAuth,
            installController: $controllers['installController'],
            contentAdminController: $controllers['contentAdminController'],
            editorModeController: $controllers['editorModeController'],
            contentController: $controllers['contentController'],
            sitemapController: $controllers['sitemapController'],
            robotsController: $controllers['robotsController']
        );

        return new Kernel(
            session: $sessionManager,
            routeRegistry: $routeRegistry,
            installState: $persistence['installState'],
            installationRequired: $persistence['installationRequired']
        );
    }
}
