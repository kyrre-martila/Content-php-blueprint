<?php

declare(strict_types=1);

use App\Http\Controller\SearchController;
use App\Http\Request;
use App\Infrastructure\View\TemplateRenderer;
use App\Infrastructure\View\TemplateResolver;

it('renders empty-query message on the system search template when q is missing', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';

    $controller = new SearchController(
        new TemplateResolver($templatesBasePath),
        new TemplateRenderer($templatesBasePath)
    );

    $request = new Request('GET', '/search', [], [], [], [], []);

    $response = $controller->index($request);

    ob_start();
    $response->send();
    $output = ob_get_clean();

    expect($output)
        ->toContain('Search results')
        ->toContain('Please enter a search query.');
});

it('renders query-aware empty results message when q exists', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';

    $controller = new SearchController(
        new TemplateResolver($templatesBasePath),
        new TemplateRenderer($templatesBasePath)
    );

    $request = new Request('GET', '/search', ['q' => 'example'], [], [], [], []);

    $response = $controller->index($request);

    ob_start();
    $response->send();
    $output = ob_get_clean();

    expect($output)
        ->toContain('No results found for "example".');
});
