<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

final class SearchController
{
    public function __construct(
        private readonly TemplateResolver $templateResolver,
        private readonly TemplateRenderer $templateRenderer
    ) {
    }

    public function index(Request $request): Response
    {
        $searchQuery = $request->queryParams()['q'] ?? '';
        $searchQuery = is_string($searchQuery) ? trim($searchQuery) : '';

        /** @var list<array{title: string, slug: string, excerpt: string}> $searchResults */
        $searchResults = [];

        $html = $this->templateRenderer->render($this->templateResolver->resolveSystemTemplate('search'), [
            'request' => $request,
            'searchQuery' => $searchQuery,
            'searchResults' => $searchResults,
        ]);

        return Response::html($html);
    }
}
