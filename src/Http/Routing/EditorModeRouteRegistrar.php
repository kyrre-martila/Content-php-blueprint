<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Admin\Controller\EditorModeController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RequireAuthMiddleware;
use App\Http\Request;
use App\Http\Response;

final class EditorModeRouteRegistrar
{
    public function __construct(
        private readonly ?EditorModeController $editorModeController,
        private readonly CsrfMiddleware $csrf,
        private readonly RequireAuthMiddleware $requireAuth,
    ) {
    }

    public function register(RouteRegistry $routeRegistry): void
    {
        if ($this->editorModeController === null) {
            return;
        }

        $enableHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->editorModeController, 'enable']
            )
        );
        $disableHandler = fn (Request $request): Response => ($this->csrf)(
            $request,
            fn (Request $csrfRequest): Response => ($this->requireAuth)(
                $csrfRequest,
                [$this->editorModeController, 'disable']
            )
        );
        $saveFieldHandler = fn (Request $request): Response => ($this->requireAuth)(
            $request,
            [$this->editorModeController, 'saveField']
        );

        $routeRegistry->post('/editor-mode/enable', $enableHandler);
        $routeRegistry->post('/editor-mode/disable', $disableHandler);
        $routeRegistry->post('/editor-mode/save-field', $saveFieldHandler);

        // System aliases
        $routeRegistry->post('/editor/enable', $enableHandler);
        $routeRegistry->post('/editor/disable', $disableHandler);
        $routeRegistry->post('/editor/save-field', $saveFieldHandler);
    }
}
