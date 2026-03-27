<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Http\Controller\ContentController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Request;
use App\Http\Response;

final class PublicContentRouteRegistrar
{
    public function __construct(
        private readonly ?ContentController $contentController,
        private readonly CsrfMiddleware $csrf,
    ) {
    }

    public function register(RouteRegistry $routeRegistry): void
    {
        if ($this->contentController === null) {
            return;
        }

        // Keep the universal catch-all route last so explicit routes always win.
        $routeRegistry->get('/{slug}', fn (Request $request): Response => ($this->csrf)(
            $request,
            [$this->contentController, 'show']
        ));
    }
}
