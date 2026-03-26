<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Content\Repository\ContentItemRepositoryInterface;
use App\Domain\Content\Repository\ContentTypeRepositoryInterface;
use App\Http\Controller\ContentController;
use App\Http\Controller\HealthController;
use App\Http\Controller\HomeController;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class Kernel
{
    public function __construct(
        private readonly string $projectRoot,
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

        $homeController = new HomeController();
        $healthController = new HealthController();

        $router->get('/', [$homeController, 'index']);
        $router->get('/health', [$healthController, 'show']);

        if ($this->contentItemRepository !== null && $this->contentTypeRepository !== null) {
            $templatesPath = $this->projectRoot . '/templates';
            $contentController = new ContentController(
                $this->contentItemRepository,
                new TemplateResolver($templatesPath),
                new TemplateRenderer($templatesPath)
            );

            $router->get('/{slug}', [$contentController, 'show']);
        }

        return $router;
    }
}
