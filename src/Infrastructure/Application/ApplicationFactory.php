<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Domain\Auth\Role;
use App\Http\Kernel;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Middleware\RequireRoleMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Routing\RouteRegistry;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Config\ConfigRepository;
use App\Infrastructure\Database\Connection;
use App\Domain\Logging\LoggerInterface;
use App\Infrastructure\Security\ClientIpResolver;
use App\Infrastructure\Security\LoginRateLimiter;

final class ApplicationFactory
{
    /** @var array<class-string, object> */
    private array $bindings;

    public function __construct(
        private readonly string $projectRoot,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger
    ) {
        $this->bindings = [
            LoggerInterface::class => $this->logger,
        ];
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

        $logger = $this->resolveLogger();
        $persistence = (new PersistenceFactory($this->config, $logger))->build();

        $upgradeRunner = new UpgradeRunner(
            $persistence['upgradeState'],
            $persistence['connection'],
            $logger
        );
        $upgradeRunner->runUpgradeIfNeeded();

        $views = (new ViewFactory($this->projectRoot, $siteUrl, $siteName))->build();

        $sessionManager = new SessionManager($sessionConfig);
        $csrf = new CsrfMiddleware($sessionManager);
        $authSession = new AuthSession($sessionManager);
        $requireAuth = new RequireAuthMiddleware($authSession);
        $requireAdminRole = new RequireRoleMiddleware(
            $authSession,
            [Role::ADMIN, Role::SUPERADMIN]
        );
        $securityHeaders = new SecurityHeadersMiddleware();
        $configuredRateLimitAttempts = $this->config->get('security.login_rate_limit_attempts', 5);
        $rateLimitAttempts = is_int($configuredRateLimitAttempts)
            ? $configuredRateLimitAttempts
            : 5;
        $configuredRateLimitWindowMinutes = $this->config->get('security.login_rate_limit_window_minutes', 10);
        $rateLimitWindowMinutes = is_int($configuredRateLimitWindowMinutes)
            ? $configuredRateLimitWindowMinutes
            : 10;
        $connection = $persistence['connection'];
        if (!$connection instanceof Connection) {
            throw new \RuntimeException('Database connection required for login rate limiting.');
        }

        $loginRateLimiter = new LoginRateLimiter(
            $connection,
            $rateLimitAttempts,
            $rateLimitWindowMinutes
        );

        $configuredTrustedProxies = $this->config->get('security.trusted_proxies', []);
        $trustedProxies = is_array($configuredTrustedProxies) ? $configuredTrustedProxies : [];
        $clientIpResolver = new ClientIpResolver($trustedProxies);

        $editors = (new EditorFactory($this->projectRoot))->build(
            $authSession,
            $sessionManager,
            $persistence['contentItemRepository'],
            $views['patternRegistry']
        );

        $exporters = (new ExporterFactory($this->projectRoot))->build(
            $persistence['repositoriesAvailable'],
            $persistence['contentTypeRepository'],
            $persistence['contentItemRepository']
        );

        $controllers = (new ControllerFactory(
            $this->projectRoot,
            $appEnvironment,
            $siteUrl,
            $logger
        ))->build(
            $persistence['userRepository'],
            $persistence['contentItemRepository'],
            $persistence['contentTypeRepository'],
            $persistence['contentRelationshipRepository'],
            $views['templateResolver'],
            $views['templateRenderer'],
            $views['patternRegistry'],
            $authSession,
            $sessionManager,
            $loginRateLimiter,
            $clientIpResolver,
            $editors['editorMode'],
            $editors['devMode'],
            $exporters['compositionExporter'],
            $exporters['ocfExporter'],
            $exporters['ocfUnavailable'],
            $editors['devFileService'],
            $editors['devModeFiles'],
            $editors['devModeHistory'],
            $persistence['appVersion'],
            $persistence['upgradeState'],
            $persistence['installState'],
            $persistence['installationRequired'],
            $persistence['repositoriesAvailable'],
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
            requireAdminRole: $requireAdminRole,
            installController: $controllers['installController'],
            contentAdminController: $controllers['contentAdminController'],
            templateAdminController: $controllers['templateAdminController'],
            contentTypeAdminController: $controllers['contentTypeAdminController'],
            relationshipAdminController: $controllers['relationshipAdminController'],
            editorModeController: $controllers['editorModeController'],
            contentController: $controllers['contentController'],
            sitemapController: $controllers['sitemapController'],
            robotsController: $controllers['robotsController']
        );

        return new Kernel(
            session: $sessionManager,
            routeRegistry: $routeRegistry,
            securityHeaders: $securityHeaders,
            installState: $persistence['installState'],
            installationRequired: $persistence['installationRequired']
        );
    }

    private function resolveLogger(): LoggerInterface
    {
        $binding = $this->bindings[LoggerInterface::class] ?? null;

        if (!$binding instanceof LoggerInterface) {
            return $this->logger;
        }

        return $binding;
    }
}
