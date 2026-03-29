<?php

declare(strict_types=1);

use App\Http\Controller\SearchController;
use App\Http\Request;
use App\Infrastructure\View\TemplateRenderer;

it('renders not implemented search response when q is missing', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';

    $controller = new SearchController(new TemplateRenderer($templatesBasePath));

    $request = new Request('GET', '/search', [], [], [], [], []);

    $response = $controller->index($request);

    ob_start();
    $response->send();
    $output = ob_get_clean();

    expect($response->status())->toBe(501)
        ->and($output)->toContain('Search functionality not implemented yet.');
});

it('renders not implemented search response when q exists', function (): void {
    $templatesBasePath = dirname(__DIR__, 3) . '/templates';

    $controller = new SearchController(new TemplateRenderer($templatesBasePath));

    $request = new Request('GET', '/search', ['q' => 'example'], [], [], [], []);

    $response = $controller->index($request);

    ob_start();
    $response->send();
    $output = ob_get_clean();

    expect($response->status())->toBe(501)
        ->and($output)->toContain('Search functionality not implemented yet.');
});
