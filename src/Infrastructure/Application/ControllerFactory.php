<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Admin\Controller\AuthController;
use App\Admin\Controller\ContentAdminController;
use App\Admin\Controller\DashboardController;
use App\Admin\Controller\DevModeController;
use App\Admin\Controller\EditorModeController;
use App\Admin\Controller\PatternController;
use App\Application\Auth\LoginUser;
use App\Application\Composition\CompositionExporter;
use App\Application\Content\CreateContentItem;
use App\Application\Content\ListContentItems;
use App\Application\Content\UpdateContentItem;
use App\Application\DevMode\DevFileService;
use App\Application\Editor\EditorContentService;
use App\Application\OCF\OCFExporter;
use App\Application\SEO\RobotsGenerator;
use App\Application\SEO\SitemapGenerator;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Controller\ContentController;
use App\Http\Controller\HealthController;
use App\Http\Controller\HomeController;
use App\Http\Controller\InstallController;
use App\Http\Controller\RobotsController;
use App\Http\Controller\SearchController;
use App\Http\Controller\SitemapController;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Editor\DevMode;
use App\Infrastructure\Editor\EditableFileRegistry;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\Editor\EditHistoryLogger;
use App\Infrastructure\Logging\Logger;
use App\Infrastructure\Pattern\PatternRegistry;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class ControllerFactory
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $appEnvironment,
        private readonly string $siteUrl,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return array{
     *   homeController: HomeController,
     *   healthController: HealthController,
     *   searchController: SearchController,
     *   authController: AuthController,
     *   dashboardController: DashboardController,
     *   patternController: PatternController,
     *   devModeController: DevModeController,
     *   installController: ?InstallController,
     *   contentAdminController: ?ContentAdminController,
     *   editorModeController: ?EditorModeController,
     *   contentController: ?ContentController,
     *   sitemapController: ?SitemapController,
     *   robotsController: RobotsController
     * }
     */
    public function build(
        UserRepositoryInterface $userRepository,
        ?ContentItemRepositoryInterface $contentItemRepository,
        ?ContentTypeRepositoryInterface $contentTypeRepository,
        TemplateResolver $templateResolver,
        TemplateRenderer $templateRenderer,
        PatternRegistry $patternRegistry,
        AuthSession $authSession,
        SessionManager $sessionManager,
        EditorMode $editorMode,
        DevMode $devMode,
        CompositionExporter $compositionExporter,
        ?OCFExporter $ocfExporter,
        bool $ocfUnavailable,
        DevFileService $devFileService,
        EditableFileRegistry $devModeFiles,
        EditHistoryLogger $devModeHistory,
        AppVersion $appVersion,
        UpgradeState $upgradeState,
        ?InstallState $installState,
        bool $installationRequired,
        bool $repositoriesAvailable,
        string $migrationsTable,
        ?EditorContentService $editorContentService
    ): array {
        $loginUser = new LoginUser($userRepository, $authSession);

        $installController = null;
        if ($installationRequired || $installState?->isInstalled() !== true) {
            $installController = new InstallController(
                $this->projectRoot,
                $templateRenderer,
                $appVersion,
                $installState,
                $migrationsTable
            );
        }

        $contentAdminController = null;
        $editorModeController = null;
        $contentController = null;
        $sitemapController = null;

        if (
            $repositoriesAvailable
            && $contentItemRepository !== null
            && $contentTypeRepository !== null
            && $editorContentService !== null
        ) {
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

            $sitemapController = new SitemapController(
                $contentItemRepository,
                new SitemapGenerator($this->siteUrl)
            );
        }

        return [
            'homeController' => new HomeController(),
            'healthController' => new HealthController(),
            'searchController' => new SearchController($templateResolver, $templateRenderer),
            'authController' => new AuthController($templateRenderer, $loginUser, $authSession, $sessionManager),
            'dashboardController' => new DashboardController($templateRenderer, $authSession, $upgradeState, $editorMode, $devMode),
            'patternController' => new PatternController($patternRegistry),
            'devModeController' => new DevModeController(
                $templateRenderer,
                $authSession,
                $sessionManager,
                $devMode,
                $compositionExporter,
                $ocfExporter,
                $ocfUnavailable,
                $devFileService,
                $devModeFiles,
                $devModeHistory,
                $this->logger,
                $this->projectRoot
            ),
            'installController' => $installController,
            'contentAdminController' => $contentAdminController,
            'editorModeController' => $editorModeController,
            'contentController' => $contentController,
            'sitemapController' => $sitemapController,
            'robotsController' => new RobotsController(new RobotsGenerator($this->appEnvironment, $this->siteUrl)),
        ];
    }
}
