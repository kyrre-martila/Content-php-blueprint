<?php

declare(strict_types=1);

use App\Application\SEO\RobotsGenerator;
use App\Http\Controller\RobotsController;
use App\Http\Request;

it('returns text/plain robots output', function (): void {
    $controller = new RobotsController(
        new RobotsGenerator('production', 'https://contentphp.martila.no')
    );

    $response = $controller->index(new Request('GET', '/robots.txt', [], [], [], [], []));

    ob_start();
    $response->send();
    $output = ob_get_clean();

    expect($response->status())->toBe(200)
        ->and($response->header('Content-Type'))->toBe('text/plain; charset=utf-8')
        ->and($output)->toContain('User-agent: *')
        ->and($output)->toContain('Sitemap: https://contentphp.martila.no/sitemap.xml');
});
