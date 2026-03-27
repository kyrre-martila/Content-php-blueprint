<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Editor\EditorMode;
use App\Infrastructure\View\TemplateRenderer;

final class DashboardController
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly AuthSession $authSession,
        private readonly ?EditorMode $editorMode = null
    ) {
    }

    public function index(Request $request): Response
    {
        $html = $this->templateRenderer->render(
            dirname(__DIR__, 3) . '/templates/admin/dashboard.php',
            [
                'request' => $request,
                'authUser' => $this->authSession->user(),
                'editorModeActive' => $this->editorMode?->isActive() ?? false,
                'editorCanEdit' => $this->editorMode?->canEdit() ?? false,
            ]
        );

        return Response::html($html);
    }
}
