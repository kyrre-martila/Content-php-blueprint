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
        $query = $request->queryParams()['q'] ?? '';
        $query = is_string($query) ? trim($query) : '';

        /** @var list<array{title: string, url: string, excerpt: string}> $results */
        $results = [];

        $html = $this->templateRenderer->render($this->templateResolver->resolveSystemTemplate('search'), [
            'request' => $request,
            'query' => $query,
            'results' => $results,
        ]);

        return Response::html($html);
    }
}
