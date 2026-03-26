<?php

declare(strict_types=1);

namespace App\Http;

use App\Admin\Controller\AuthController;
use App\Admin\Controller\DashboardController;
use App\Application\Auth\LoginUser;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Controller\ContentController;
use App\Http\Controller\HealthController;
use App\Http\Controller\HomeController;
use App\Http\Middleware\RequireAuthMiddleware;
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
        private readonly ?ContentItemRepositoryInterface $contentItemRepository = null,
        private readonly ?ContentTypeRepositoryInterface $contentTypeRepository = null
    ) {
    }

    public function handle(Request $request): Response
    {
        $this->session->start();
        $router = $this->buildRouter();

        return $router->dispatch($request);
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

        $router->get('/', [$homeController, 'index']);
        $router->get('/health', [$healthController, 'show']);

        $router->get('/admin/login', [$authController, 'showLogin']);
        $router->post('/admin/login', [$authController, 'login']);
        $router->post('/admin/logout', static fn (Request $request): Response => $requireAuth(
            $request,
            [$authController, 'logout']
        ));
        $router->get('/admin', static fn (Request $request): Response => $requireAuth(
            $request,
            [$dashboardController, 'index']
        ));

        if ($this->contentItemRepository !== null && $this->contentTypeRepository !== null) {
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
