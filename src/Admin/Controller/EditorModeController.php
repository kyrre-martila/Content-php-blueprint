<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Application\Editor\EditorContentService;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Editor\EditableFieldValidationException;
use App\Infrastructure\Editor\EditorMode;
use InvalidArgumentException;
use Throwable;

final class EditorModeController
{
    public function __construct(
        private readonly EditorMode $editorMode,
        private readonly ?EditorContentService $editorContentService = null
    ) {
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

    public function saveField(Request $request): Response
    {
        if (!$this->editorMode->canUse()) {
            return Response::json(['success' => false, 'error' => 'forbidden'], 403);
        }

        if (!$this->editorMode->isActive()) {
            return Response::json(['success' => false, 'error' => 'editor_mode_inactive'], 409);
        }

        if ($this->editorContentService === null) {
            return Response::json(['success' => false, 'error' => 'editor_mode_unavailable'], 500);
        }

        try {
            $savedItem = $this->editorContentService->saveInlineEdit($request->postParams());
        } catch (EditableFieldValidationException $exception) {
            return Response::json(['success' => false, 'error' => $exception->getMessage()], 422);
        } catch (InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'error' => $exception->getMessage()], 422);
        } catch (Throwable) {
            return Response::json(['success' => false, 'error' => 'save_failed'], 500);
        }

        return Response::json([
            'success' => true,
            'content_id' => $savedItem->id(),
            'updated_at' => $savedItem->updatedAt()->format(DATE_ATOM),
        ]);
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
