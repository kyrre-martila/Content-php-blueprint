<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\View\TemplateRenderer;

final class SearchController
{
    public function __construct(private readonly TemplateRenderer $templateRenderer)
    {
    }

    public function index(Request $request): Response
    {
        // TODO: Implement search via ContentRepository full-text search.
        // TODO: Add MySQL MATCH AGAINST support.
        // TODO: Add SQLite fallback LIKE search.
        $html = $this->templateRenderer->renderTemplate('errors/501.php', [
            'request' => $request,
            'message' => 'Search functionality not implemented yet.',
        ]);

        return Response::html($html, 501);
    }
}
