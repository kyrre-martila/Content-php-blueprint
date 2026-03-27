<?php

declare(strict_types=1);

namespace App\Http;

use App\Admin\Controller\AuthController;
use App\Admin\Controller\ContentAdminController;
use App\Admin\Controller\DashboardController;
use App\Admin\Controller\DevModeController;
use App\Admin\Controller\EditorModeController;
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
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Infrastructure\Application\InstallState;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
use App\Infrastructure\Editor\DevMode;
use App\Infrastructure\Editor\EditableFileRegistry;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\Editor\EditHistoryLogger;
use App\Infrastructure\Logging\Logger;
use App\Infrastructure\Pattern\PatternRegistry;
use App\Infrastructure\Pattern\PatternRenderer;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class Kernel
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly SessionManager $session,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ?InstallState $installState = null,
        private readonly ?ContentItemRepositoryInterface $contentItemRepository = null,
        private readonly ?ContentTypeRepositoryInterface $contentTypeRepository = null,
        private readonly bool $installationRequired = false,
        private readonly string $migrationsTable = 'phinxlog'
    ) {
    }

    public function handle(Request $request): Response
    {
        if ($this->shouldRedirectToInstall($request)) {
            return Response::redirect('/install');
        }

        $this->session->start();
        $router = $this->buildRouter();

        return $router->dispatch($request);
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

        $homeController = new HomeController();
        $healthController = new HealthController();
        $templatesPath = $this->projectRoot . '/templates';
        $patternRegistry = new PatternRegistry($this->projectRoot . '/patterns');
        $patternRenderer = new PatternRenderer($patternRegistry);
        $renderer = new TemplateRenderer($templatesPath, $patternRenderer);
        $authSession = new AuthSession($this->session);
        $editorMode = new EditorMode($authSession, $this->session);
        $devMode = new DevMode($this->projectRoot, $authSession, $this->session);
        $devModeFiles = new EditableFileRegistry($this->projectRoot, $devMode);
        $devModeHistory = new EditHistoryLogger($this->projectRoot . '/storage/logs/dev-mode-edits.log');
        $logger = new Logger($this->projectRoot . '/storage/logs');
        $loginUser = new LoginUser($this->userRepository, $authSession);
        $authController = new AuthController($renderer, $loginUser, $authSession, $this->session);
        $dashboardController = new DashboardController($renderer, $authSession, $editorMode, $devMode);
        $devModeController = new DevModeController(
            $renderer,
            $authSession,
            $this->session,
            $devMode,
            $devModeFiles,
            $devModeHistory,
            $logger
        );
        $requireAuth = new RequireAuthMiddleware($authSession);
        $csrf = new CsrfMiddleware($this->session);

        $router->get('/', [$homeController, 'index']);
        $router->get('/health', [$healthController, 'show']);

        if ($this->installationRequired || $this->installState?->isInstalled() !== true) {
            $installController = new InstallController(
                $this->projectRoot,
                $renderer,
                $this->installState,
                $this->migrationsTable
            );

            $router->get('/install', static fn (Request $request): Response => $csrf(
                $request,
                [$installController, 'show']
            ));
            $router->post('/install', static fn (Request $request): Response => $csrf(
                $request,
                [$installController, 'install']
            ));
        } else {
            $router->get('/install', static fn (Request $request): Response => Response::redirect('/'));
        }

        $router->get('/admin/login', static fn (Request $request): Response => $csrf(
            $request,
            [$authController, 'showLogin']
        ));
        $router->post('/admin/login', static fn (Request $request): Response => $csrf(
            $request,
            [$authController, 'login']
        ));
        $router->post('/admin/logout', static fn (Request $request): Response => $csrf(
            $request,
            static fn (Request $csrfRequest): Response => $requireAuth(
                $csrfRequest,
                [$authController, 'logout']
            )
        ));
        $router->get('/admin', static fn (Request $request): Response => $csrf(
            $request,
            static fn (Request $csrfRequest): Response => $requireAuth(
                $csrfRequest,
                [$dashboardController, 'index']
            )
        ));

        $router->post('/admin/dev-mode/enable', static fn (Request $request): Response => $csrf(
            $request,
            static fn (Request $csrfRequest): Response => $requireAuth(
                $csrfRequest,
                [$devModeController, 'enable']
            )
        ));
        $router->post('/admin/dev-mode/disable', static fn (Request $request): Response => $csrf(
            $request,
            static fn (Request $csrfRequest): Response => $requireAuth(
                $csrfRequest,
                [$devModeController, 'disable']
            )
        ));
        $router->get('/admin/dev-mode', static fn (Request $request): Response => $csrf(
            $request,
            static fn (Request $csrfRequest): Response => $requireAuth(
                $csrfRequest,
                [$devModeController, 'index']
            )
        ));
        $router->get('/admin/dev-mode/edit', static fn (Request $request): Response => $csrf(
            $request,
            static fn (Request $csrfRequest): Response => $requireAuth(
                $csrfRequest,
                [$devModeController, 'edit']
            )
        ));
        $router->post('/admin/dev-mode/edit', static fn (Request $request): Response => $csrf(
            $request,
            static fn (Request $csrfRequest): Response => $requireAuth(
                $csrfRequest,
                [$devModeController, 'update']
            )
        ));

        if ($this->contentItemRepository !== null && $this->contentTypeRepository !== null) {
            $listContentItems = new ListContentItems($this->contentItemRepository, $this->contentTypeRepository);
            $createContentItem = new CreateContentItem($this->contentItemRepository, $this->contentTypeRepository);
            $updateContentItem = new UpdateContentItem($this->contentItemRepository, $this->contentTypeRepository);
            $contentAdminController = new ContentAdminController(
                $renderer,
                $this->contentTypeRepository,
                $this->contentItemRepository,
                $listContentItems,
                $createContentItem,
                $updateContentItem,
                $patternRegistry,
                $authSession,
                $this->session
            );
            $editorModeController = new EditorModeController(
                $editorMode,
                $this->contentItemRepository,
                $patternRegistry
            );

            $router->get('/admin/content', static fn (Request $request): Response => $csrf(
                $request,
                static fn (Request $csrfRequest): Response => $requireAuth(
                    $csrfRequest,
                    [$contentAdminController, 'index']
                )
            ));
            $router->get('/admin/content/create', static fn (Request $request): Response => $csrf(
                $request,
                static fn (Request $csrfRequest): Response => $requireAuth(
                    $csrfRequest,
                    [$contentAdminController, 'create']
                )
            ));
            $router->post('/admin/content/create', static fn (Request $request): Response => $csrf(
                $request,
                static fn (Request $csrfRequest): Response => $requireAuth(
                    $csrfRequest,
                    [$contentAdminController, 'store']
                )
            ));
            $router->get('/admin/content/{id}/edit', static fn (Request $request): Response => $csrf(
                $request,
                static fn (Request $csrfRequest): Response => $requireAuth(
                    $csrfRequest,
                    [$contentAdminController, 'edit']
                )
            ));
            $router->post('/admin/content/{id}/edit', static fn (Request $request): Response => $csrf(
                $request,
                static fn (Request $csrfRequest): Response => $requireAuth(
                    $csrfRequest,
                    [$contentAdminController, 'update']
                )
            ));
            $router->post('/admin/editor-mode/enable', static fn (Request $request): Response => $csrf(
                $request,
                static fn (Request $csrfRequest): Response => $requireAuth(
                    $csrfRequest,
                    [$editorModeController, 'enable']
                )
            ));
            $router->post('/admin/editor-mode/disable', static fn (Request $request): Response => $csrf(
                $request,
                static fn (Request $csrfRequest): Response => $requireAuth(
                    $csrfRequest,
                    [$editorModeController, 'disable']
                )
            ));
            $router->post('/admin/editor-mode/update', static fn (Request $request): Response => $csrf(
                $request,
                static fn (Request $csrfRequest): Response => $requireAuth(
                    $csrfRequest,
                    [$editorModeController, 'update']
                )
            ));

            $contentController = new ContentController(
                $this->contentItemRepository,
                new TemplateResolver($templatesPath),
                $renderer,
                $editorMode
            );

            $router->get('/{slug}', static fn (Request $request): Response => $csrf(
                $request,
                [$contentController, 'show']
            ));
        }

        return $router;
    }
}
