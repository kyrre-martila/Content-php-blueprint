<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Controller\HealthController;
use App\Http\Controller\HomeController;

final class Kernel
{
    public function __construct(
        private readonly ?ContentItemRepositoryInterface $contentItemRepository = null,
        private readonly ?ContentTypeRepositoryInterface $contentTypeRepository = null
    ) {
    }

    public function handle(Request $request): Response
    {
        $router = $this->buildRouter();

        return $router->dispatch($request);
    }

    private function buildRouter(): Router
    {
        $router = new Router();

        $repositoriesAvailable = $this->contentItemRepository !== null && $this->contentTypeRepository !== null;

        if ($repositoriesAvailable) {
            // Repositories are intentionally instantiated during bootstrap and available for upcoming content routes.
        }

        $homeController = new HomeController();
        $healthController = new HealthController();

        $router->get('/', [$homeController, 'index']);
        $router->get('/health', [$healthController, 'show']);

        return $router;
    }
}
