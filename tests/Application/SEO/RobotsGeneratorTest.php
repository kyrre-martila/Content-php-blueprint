<?php

declare(strict_types=1);

use App\Application\SEO\RobotsGenerator;

it('blocks all indexing outside production environments', function (): void {
    $generator = new RobotsGenerator('local', 'https://contentphp.martila.no');

    $robots = $generator->generate();

    expect($robots)
        ->toContain("User-agent: *\nDisallow: /")
        ->not->toContain('Sitemap:');
});

it('allows production indexing while disallowing privileged routes and including sitemap', function (): void {
    $generator = new RobotsGenerator('production', 'https://contentphp.martila.no/');

    $robots = $generator->generate();

    expect($robots)
        ->toContain('User-agent: *')
        ->toContain('Allow: /')
        ->toContain('Disallow: /admin')
        ->toContain('Disallow: /editor')
        ->toContain('Disallow: /editor-mode')
        ->toContain('Disallow: /dev')
        ->toContain('Disallow: /install')
        ->toContain('Sitemap: https://contentphp.martila.no/sitemap.xml');
});
