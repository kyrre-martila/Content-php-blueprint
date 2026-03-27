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

    /** @var array{
     *   kind: string,
     *   scope: string,
     *   export_format_version: int,
     *   slug: string,
     *   title: string,
     *   route_type: string,
     *   renderer_entrypoint: string,
     *   layout: string,
     *   patterns: list<array{pattern: string, data: array<string, string>}>
     * } $aboutExport
     */
    $aboutExport = json_decode((string) file_get_contents($paths[0]), true, 512, JSON_THROW_ON_ERROR);

    expect($aboutExport)
        ->toMatchArray([
            'kind' => 'blueprint-composition-snapshot',
            'scope' => 'content-routes',
            'export_format_version' => 2,
            'slug' => 'about',
            'title' => 'About us',
            'route_type' => 'content',
            'renderer_entrypoint' => 'templates/index.php',
            'layout' => 'templates/layout.php',
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

    /** @var array{
     *   kind: string,
     *   scope: string,
     *   export_format_version: int,
     *   routes: list<array{route: string, renderer_entrypoint: string, layout: string, patterns: list<array{pattern: string, data: array<string, string>}>}>
     * } $payload
     */
    $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    expect($path)->toEndWith('/storage/exports/composition/system-routes.json')
        ->and($payload['kind'])->toBe('blueprint-composition-snapshot')
        ->and($payload['scope'])->toBe('system-routes')
        ->and($payload['export_format_version'])->toBe(2)
        ->and($payload['routes'])->toBe([
            ['route' => 'search', 'renderer_entrypoint' => 'templates/system/search.php', 'layout' => 'templates/layout.php', 'patterns' => []],
            ['route' => '404', 'renderer_entrypoint' => 'templates/system/404.php', 'layout' => 'templates/layout.php', 'patterns' => []],
        ]);
});
