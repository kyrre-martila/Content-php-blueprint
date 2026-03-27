<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Editor\EditorMode;

final class EditorModeController
{
    public function __construct(private readonly EditorMode $editorMode)
    {
    }

    public function enable(Request $request): Response
    {
        if (!$this->editorMode->canUse()) {
            return Response::html('<h1>403 Forbidden</h1>', 403);
        }

        $this->editorMode->enable();

        return Response::redirect($this->redirectTarget($request));
    }

    public function disable(Request $request): Response
    {
        if (!$this->editorMode->canUse()) {
            return Response::html('<h1>403 Forbidden</h1>', 403);
        }

        $this->editorMode->disable();

        return Response::redirect($this->redirectTarget($request));
    }

    private function redirectTarget(Request $request): string
    {
        $referer = $request->serverParams()['HTTP_REFERER'] ?? null;

        if (!is_string($referer) || trim($referer) === '') {
            return '/';
        }

        $path = parse_url($referer, PHP_URL_PATH);

        if (!is_string($path) || trim($path) === '') {
            return '/';
        }

        $query = parse_url($referer, PHP_URL_QUERY);

        if (!is_string($query) || trim($query) === '') {
            return $path;
        }

        return $path . '?' . $query;
    }
}
