<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Admin\Controller\AuthController;
use App\Admin\Controller\ContentAdminController;
use App\Admin\Controller\DashboardController;
use App\Admin\Controller\DevModeController;
use App\Admin\Controller\EditorModeController;
use App\Admin\Controller\PatternController;
use App\Application\Composition\CompositionExporter;
use App\Application\DevMode\DevFileService;
use App\Application\Editor\EditorContentService;
use App\Application\OCF\OCFExporter;
use App\Application\Auth\LoginUser;
use App\Application\Content\CreateContentItem;
use App\Application\Content\ListContentItems;
use App\Application\Content\UpdateContentItem;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Controller\ContentController;
use App\Http\Controller\HealthController;
use App\Http\Controller\HomeController;
use App\Http\Controller\InstallController;
use App\Http\Controller\SearchController;
use App\Http\Kernel;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Routing\RouteRegistry;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\MySqlUserRepository;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Config\ConfigRepository;
use App\Infrastructure\Content\MySqlContentItemRepository;
use App\Infrastructure\Content\MySqlContentTypeRepository;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\PdoFactory;
use App\Infrastructure\Editor\DevMode;
use App\Infrastructure\Editor\EditableFieldRenderer;
use App\Infrastructure\Editor\EditableFieldValidator;
use App\Infrastructure\Editor\EditableFileRegistry;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\Editor\EditHistoryLogger;
use App\Infrastructure\Logging\Logger;
use App\Infrastructure\Pattern\PatternDataValidator;
use App\Infrastructure\Pattern\PatternRegistry;
use App\Infrastructure\View\PatternRenderer;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

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
        /** @var string $migrationsTable */
        $migrationsTable = (string) $this->config->get('database.migrations.table', 'phinxlog');

        $installState = null;
        $connection = null;
        $installationRequired = false;

        try {
            /** @var array<string, mixed> $connectionConfig */
            $connectionConfig = $this->config->get('database.connections.mysql', []);

            $pdo = (new PdoFactory())->create($connectionConfig);
            $connection = new Connection($pdo);
            $installState = new InstallState($connection, $migrationsTable);
        } catch (\RuntimeException $runtimeException) {
            $installationRequired = true;
            $this->logger->warning('Database bootstrap unavailable, forcing install flow.', [
                'error' => $runtimeException->getMessage(),
            ]);
        }

        [$userRepository, $contentItemRepository, $contentTypeRepository] = $this->buildRepositories($connection);

        $sessionManager = new SessionManager($sessionConfig);
        $csrf = new CsrfMiddleware($sessionManager);
        $authSession = new AuthSession($sessionManager);
        $requireAuth = new RequireAuthMiddleware($authSession);

        $templatesPath = $this->projectRoot . '/templates';
        $patternRegistry = new PatternRegistry($this->projectRoot . '/patterns');
        $templateResolver = new TemplateResolver($templatesPath);

        $editableFieldRenderer = new EditableFieldRenderer();
        $patternDataValidator = new PatternDataValidator();
        $patternRenderer = new PatternRenderer($patternRegistry, $patternDataValidator, $editableFieldRenderer);
        $templateRenderer = new TemplateRenderer($templatesPath, $patternRenderer, $editableFieldRenderer);

        $editorMode = new EditorMode($authSession, $sessionManager);
        $devMode = new DevMode($this->projectRoot, $authSession, $sessionManager);
        $devFileService = new DevFileService($this->projectRoot);
        $devModeFiles = new EditableFileRegistry($this->projectRoot, $devMode);
        $devModeHistory = new EditHistoryLogger($this->projectRoot . '/storage/logs/dev-mode-edits.log');
        $compositionExporter = new CompositionExporter($this->projectRoot);
        $loginUser = new LoginUser($userRepository, $authSession);

        $installController = null;
        if ($installationRequired || $installState?->isInstalled() !== true) {
            $installController = new InstallController(
                $this->projectRoot,
                $templateRenderer,
                $installState,
                $migrationsTable
            );
        }

        $contentAdminController = null;
        $editorModeController = null;
        $contentController = null;

        if ($contentItemRepository !== null && $contentTypeRepository !== null) {
            $ocfExporter = new OCFExporter(
                $contentTypeRepository,
                $contentItemRepository,
                $this->projectRoot
            );
            $listContentItems = new ListContentItems($contentItemRepository, $contentTypeRepository);
            $createContentItem = new CreateContentItem($contentItemRepository, $contentTypeRepository);
            $updateContentItem = new UpdateContentItem($contentItemRepository, $contentTypeRepository);

            $contentAdminController = new ContentAdminController(
                $templateRenderer,
                $contentTypeRepository,
                $contentItemRepository,
                $listContentItems,
                $createContentItem,
                $updateContentItem,
                $patternRegistry,
                $authSession,
                $sessionManager
            );

            $editableFieldValidator = new EditableFieldValidator(
                $editorMode,
                $contentItemRepository,
                $patternRegistry
            );

            $editorContentService = new EditorContentService(
                $contentItemRepository,
                $editableFieldValidator
            );

            $editorModeController = new EditorModeController(
                $editorMode,
                $editorContentService
            );

            $contentController = new ContentController(
                $contentItemRepository,
                $templateResolver,
                $templateRenderer,
                $editorMode
            );
        } else {
            $temporaryConnection = new Connection((new \PDO('sqlite::memory:')));
            $ocfExporter = new OCFExporter(
                new MySqlContentTypeRepository($temporaryConnection),
                new MySqlContentItemRepository($temporaryConnection),
                $this->projectRoot
            );
        }

        $routeRegistry = new RouteRegistry(
            homeController: new HomeController(),
            healthController: new HealthController(),
            searchController: new SearchController($templateResolver, $templateRenderer),
            authController: new AuthController($templateRenderer, $loginUser, $authSession, $sessionManager),
            dashboardController: new DashboardController($templateRenderer, $authSession, $editorMode, $devMode),
            patternController: new PatternController($patternRegistry),
            devModeController: new DevModeController(
                $templateRenderer,
                $authSession,
                $sessionManager,
                $devMode,
                $compositionExporter,
                $ocfExporter,
                $devFileService,
                $devModeFiles,
                $devModeHistory,
                $this->logger,
                $this->projectRoot
            ),
            csrf: $csrf,
            requireAuth: $requireAuth,
            installController: $installController,
            contentAdminController: $contentAdminController,
            editorModeController: $editorModeController,
            contentController: $contentController
        );

        return new Kernel(
            session: $sessionManager,
            routeRegistry: $routeRegistry,
            installState: $installState,
            installationRequired: $installationRequired
        );
    }

    /**
     * @return array{0: UserRepositoryInterface, 1: ?ContentItemRepositoryInterface, 2: ?ContentTypeRepositoryInterface}
     */
    private function buildRepositories(?Connection $connection): array
    {
        if ($connection === null) {
            $temporaryConnection = new Connection((new \PDO('sqlite::memory:')));

            return [
                new MySqlUserRepository($temporaryConnection),
                null,
                null,
            ];
        }

        return [
            new MySqlUserRepository($connection),
            new MySqlContentItemRepository($connection),
            new MySqlContentTypeRepository($connection),
        ];
    }
}
