<?php

declare(strict_types=1);

namespace App\Http;

use App\Admin\Controller\AuthController;
use App\Admin\Controller\ContentAdminController;
use App\Admin\Controller\DashboardController;
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
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Infrastructure\Application\InstallState;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;
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
        private readonly ?ContentTypeRepositoryInterface $contentTypeRepository = null
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
        if ($this->installState === null || $this->installState->isInstalled()) {
            return false;
        }

        return $this->isSetupDependentAdminPath($request->path());
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
        $renderer = new TemplateRenderer($templatesPath);
        $authSession = new AuthSession($this->session);
        $loginUser = new LoginUser($this->userRepository, $authSession);
        $authController = new AuthController($renderer, $loginUser, $authSession, $this->session);
        $dashboardController = new DashboardController($renderer, $authSession);
        $requireAuth = new RequireAuthMiddleware($authSession);
        $csrf = new CsrfMiddleware($this->session);

        $router->get('/', [$homeController, 'index']);
        $router->get('/health', [$healthController, 'show']);

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
                $authSession,
                $this->session
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

            $contentController = new ContentController(
                $this->contentItemRepository,
                new TemplateResolver($templatesPath),
                $renderer
            );

            $router->get('/{slug}', [$contentController, 'show']);
        }

        return $router;
    }
}
