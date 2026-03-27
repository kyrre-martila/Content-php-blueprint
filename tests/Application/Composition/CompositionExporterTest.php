<?php

declare(strict_types=1);

use App\Application\Composition\CompositionExporter;
use App\Domain\Content\ContentItem;
use App\Domain\Content\ContentStatus;
use App\Domain\Content\ContentType;
use App\Domain\Content\Slug;

it('exports one composition JSON file per content item with ordered pattern blocks', function (): void {
    $projectRoot = sys_get_temp_dir() . '/composition-exporter-' . bin2hex(random_bytes(6));

    mkdir($projectRoot . '/storage', 0775, true);

    $type = new ContentType('page', 'Page', 'templates/index.php');

    $about = new ContentItem(
        id: 1,
        type: $type,
        title: 'About us',
        slug: Slug::fromString('about'),
        status: ContentStatus::Published,
        createdAt: new DateTimeImmutable('2026-03-27T10:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2026-03-27T10:00:00+00:00'),
        patternBlocks: [
            [
                'pattern' => 'hero',
                'data' => [
                    'title' => 'About us',
                    'subtitle' => 'Our story',
                ],
            ],
            [
                'pattern' => 'text-block',
                'data' => [
                    'text' => 'Example body text',
                ],
            ],
        ]
    );

    $home = new ContentItem(
        id: 2,
        type: $type,
        title: 'Home',
        slug: Slug::fromString('home'),
        status: ContentStatus::Published,
        createdAt: new DateTimeImmutable('2026-03-27T10:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2026-03-27T10:00:00+00:00'),
        patternBlocks: [
            [
                'pattern' => 'cta',
                'data' => [
                    'title' => 'Start here',
                ],
            ],
        ]
    );

    $exporter = new CompositionExporter($projectRoot);

    $paths = $exporter->exportAll([$about, $home]);

    expect($paths)
        ->toHaveCount(2)
        ->and($paths[0])->toEndWith('/storage/exports/composition/about.json')
        ->and($paths[1])->toEndWith('/storage/exports/composition/home.json')
        ->and(is_dir($projectRoot . '/storage/exports/composition'))->toBeTrue();

    /** @var array{slug: string, title: string, template: string, patterns: list<array{pattern: string, data: array<string, string>}>} $aboutExport */
    $aboutExport = json_decode((string) file_get_contents($paths[0]), true, 512, JSON_THROW_ON_ERROR);

    expect($aboutExport)
        ->toMatchArray([
            'slug' => 'about',
            'title' => 'About us',
            'template' => 'templates/index.php',
        ])
        ->and($aboutExport['patterns'])->toBe([
            [
                'pattern' => 'hero',
                'data' => [
                    'title' => 'About us',
                    'subtitle' => 'Our story',
                ],
            ],
            [
                'pattern' => 'text-block',
                'data' => [
                    'text' => 'Example body text',
                ],
            ],
        ]);
});

it('can export a minimal system-routes composition snapshot separate from OCF', function (): void {
    $projectRoot = sys_get_temp_dir() . '/composition-system-routes-' . bin2hex(random_bytes(6));

    mkdir($projectRoot . '/storage', 0775, true);

    $exporter = new CompositionExporter($projectRoot);

    $path = $exporter->exportSystemRoutes();

    /** @var array{kind: string, scope: string, routes: list<array{route: string, template: string, patterns: list<array{pattern: string, data: array<string, string>}>}>} $payload */
    $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    expect($path)->toEndWith('/storage/exports/composition/system-routes.json')
        ->and($payload['kind'])->toBe('blueprint-composition-snapshot')
        ->and($payload['scope'])->toBe('system-routes')
        ->and($payload['routes'])->toBe([
            ['route' => 'search', 'template' => 'templates/system/search.php', 'patterns' => []],
            ['route' => '404', 'template' => 'templates/system/404.php', 'patterns' => []],
        ]);
});
