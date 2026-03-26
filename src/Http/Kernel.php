<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Controller\HealthController;
use App\Http\Controller\HomeController;

final class Kernel
{
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

        return $router;
    }
}
