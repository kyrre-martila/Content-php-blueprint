<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Admin\Controller\AuthController;
use App\Admin\Controller\ContentAdminController;
use App\Admin\Controller\CategoryAdminController;
use App\Admin\Controller\ContentTypeAdminController;
use App\Admin\Controller\DashboardController;
use App\Admin\Controller\DevModeController;
use App\Admin\Controller\EditorModeController;
use App\Admin\Controller\FileAdminController;
use App\Admin\Controller\PatternController;
use App\Admin\Controller\RelationshipAdminController;
use App\Admin\Controller\TemplateAdminController;
use App\Admin\Security\EditorSafeContentPolicy;
use App\Application\Auth\LoginUser;
use App\Application\Composition\CompositionExporter;
use App\Application\Content\ContentTypeFieldSchemaService;
use App\Application\Content\CreateContentItem;
use App\Application\Content\ListContentItems;
use App\Application\Content\UpdateContentItem;
use App\Application\DevMode\DevFileService;
use App\Application\Files\DeleteFileService;
use App\Application\Files\ContentItemFileFieldResolver;
use App\Application\Files\FileUploadService;
use App\Application\Files\UpdateFileMetadataService;
use App\Application\Editor\EditorContentService;
use App\Application\OCF\OCFExporter;
use App\Application\SEO\RobotsGenerator;
use App\Application\SEO\SitemapGenerator;
use App\Application\Validation\ContentItemFieldValueValidator;
use App\Application\Validation\ContentItemValidator;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentRelationshipRepositoryInterface;
use App\Domain\Content\Repository\CategoryGroupRepositoryInterface;
use App\Domain\Content\Repository\CategoryRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Domain\Files\Repository\FileRepositoryInterface;
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
use App\Infrastructure\Files\FileStorageInterface;
use App\Domain\Logging\LoggerInterface;
use App\Infrastructure\Pattern\PatternRegistry;
use App\Infrastructure\Security\ClientIpResolver;
use App\Infrastructure\Security\LoginRateLimiter;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplatePathMap;
use App\Infrastructure\View\TemplateResolver;

final class ControllerFactory
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $appEnvironment,
        private readonly string $siteUrl,
        private readonly LoggerInterface $logger
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
 *   templateAdminController: ?TemplateAdminController,
 *   fileAdminController: ?FileAdminController,
     *   contentTypeAdminController: ?ContentTypeAdminController,
     *   categoryAdminController: ?CategoryAdminController,
     *   relationshipAdminController: ?RelationshipAdminController,
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
        ?ContentRelationshipRepositoryInterface $contentRelationshipRepository,
        ?CategoryGroupRepositoryInterface $categoryGroupRepository,
        ?CategoryRepositoryInterface $categoryRepository,
        FileRepositoryInterface $fileRepository,
        TemplateResolver $templateResolver,
        TemplatePathMap $templatePathMap,
        TemplateRenderer $templateRenderer,
        PatternRegistry $patternRegistry,
        AuthSession $authSession,
        SessionManager $sessionManager,
        LoginRateLimiter $loginRateLimiter,
        ClientIpResolver $clientIpResolver,
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
        ?EditorContentService $editorContentService,
        FileStorageInterface $fileStorage
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
        $templateAdminController = null;
        $contentTypeAdminController = null;
        $categoryAdminController = null;
        $fileAdminController = null;
        $editorModeController = null;
        $contentController = null;
        $sitemapController = null;

        if (
            $repositoriesAvailable
            && $contentItemRepository !== null
            && $contentTypeRepository !== null
            && $editorContentService !== null
        ) {
            $listContentItems = new ListContentItems($contentItemRepository);
            $contentItemValidator = new ContentItemValidator();
            $fieldValueValidator = new ContentItemFieldValueValidator($fileRepository);
            $createContentItem = new CreateContentItem($contentItemRepository, $contentTypeRepository, $contentItemValidator, $fieldValueValidator);
            $updateContentItem = new UpdateContentItem($contentItemRepository, $contentTypeRepository, $contentItemValidator, $fieldValueValidator);

            $contentAdminController = new ContentAdminController(
                $templateRenderer,
                $contentTypeRepository,
                $contentItemRepository,
                $fileRepository,
                $listContentItems,
                $createContentItem,
                $updateContentItem,
                $patternRegistry,
                $authSession,
                $sessionManager,
                new EditorSafeContentPolicy()
            );

            $templateAdminController = new TemplateAdminController(
                $templateRenderer,
                $contentTypeRepository,
                $categoryGroupRepository ?? throw new \RuntimeException('Category group repository is required for template admin.'),
                $authSession,
                $sessionManager,
                $templateResolver,
                $templatePathMap,
                $devModeFiles,
                $devFileService,
                $this->logger
            );

            $contentTypeAdminController = new ContentTypeAdminController(
                $templateRenderer,
                $contentTypeRepository,
                $categoryGroupRepository ?? throw new \RuntimeException('Category group repository is required for content type admin.'),
                $contentRelationshipRepository ?? throw new \RuntimeException('Relationship repository is required for content type admin.'),
                $authSession,
                $sessionManager,
                $templateResolver,
                $templatePathMap,
                new ContentTypeFieldSchemaService()
            );

            if ($categoryGroupRepository !== null && $categoryRepository !== null) {
                $categoryAdminController = new CategoryAdminController(
                    $templateRenderer,
                    $categoryGroupRepository,
                    $categoryRepository,
                    $authSession,
                    $sessionManager
                );
            }

            $fileAdminController = new FileAdminController(
                $templateRenderer,
                $fileRepository,
                new FileUploadService($fileRepository, $fileStorage),
                new UpdateFileMetadataService($fileRepository),
                new DeleteFileService($fileRepository, $fileStorage),
                $authSession,
                $sessionManager
            );

            $editorModeController = new EditorModeController(
                $editorMode,
                $editorContentService
            );

            $contentController = new ContentController(
                $categoryGroupRepository ?? throw new \RuntimeException('Category group repository is required for content controller.'),
                $categoryRepository ?? throw new \RuntimeException('Category repository is required for content controller.'),
                $contentItemRepository,
                new ContentItemFileFieldResolver($fileRepository),
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
            'searchController' => new SearchController($templateRenderer),
            'authController' => new AuthController(
                $templateRenderer,
                $loginUser,
                $authSession,
                $sessionManager,
                $loginRateLimiter,
                $clientIpResolver
            ),
            'dashboardController' => new DashboardController($templateRenderer, $authSession, $upgradeState, $templateResolver, $templatePathMap, $contentTypeRepository, $editorMode, $devMode),
            'patternController' => new PatternController($patternRegistry),
            'templateAdminController' => $templateAdminController,
            'fileAdminController' => $fileAdminController,
            'contentTypeAdminController' => $contentTypeAdminController,
            'categoryAdminController' => $categoryAdminController,
            'relationshipAdminController' => $repositoriesAvailable
                && $contentItemRepository !== null
                && $contentTypeRepository !== null
                && $contentRelationshipRepository !== null
                ? new RelationshipAdminController(
                    $templateRenderer,
                    $contentTypeRepository,
                    $contentItemRepository,
                    $contentRelationshipRepository,
                    $authSession,
                    $sessionManager
                )
                : null,
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
