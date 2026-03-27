<?php

declare(strict_types=1);

namespace App\Application\Composition;

use App\Domain\Content\ContentItem;
use RuntimeException;

/**
 * Exports blueprint-specific page composition snapshots for AI/tooling context.
 *
 * This export is intentionally separate from OCF content export:
 * - OCF remains portable structured content only.
 * - Composition snapshot captures runtime assembly metadata only.
 *
 * Composition snapshots intentionally do not represent:
 * - content-model semantics (handled by OCF)
 * - template hierarchy assumptions
 * - presentation source code
 */
final class CompositionExporter
{
    /**
     * @var list<array{route: string, renderer_entrypoint: string, layout: string, patterns: list<array{pattern: string, data: array<string, string>}>}>
     */
    private array $systemRouteComposition;

    /**
     * @param list<array{route: string, renderer_entrypoint: string, layout: string, patterns: list<array{pattern: string, data: array<string, string>}>}> $systemRouteComposition
     */
    public function __construct(
        private readonly string $projectRoot,
        array $systemRouteComposition = [
            ['route' => 'search', 'renderer_entrypoint' => 'templates/system/search.php', 'layout' => 'templates/layout.php', 'patterns' => []],
            ['route' => '404', 'renderer_entrypoint' => 'templates/system/404.php', 'layout' => 'templates/layout.php', 'patterns' => []],
        ]
    ) {
        $this->systemRouteComposition = $systemRouteComposition;
    }

    public function exportContentItem(ContentItem $item): string
    {
        $this->ensureExportDirectoryExists();

        $snapshot = [
            'kind' => 'blueprint-composition-snapshot',
            'scope' => 'content-routes',
            'export_format_version' => 2,
            'slug' => $item->slug()->value(),
            'title' => $item->title(),
            'route_type' => 'content',
            'renderer_entrypoint' => 'templates/index.php',
            'layout' => 'templates/layout.php',
            'patterns' => array_map(
                static fn (array $block): array => [
                    'pattern' => $block['pattern'],
                    'data' => $block['data'],
                ],
                $item->patternBlocks()
            ),
        ];

        $path = $this->exportDirectory() . '/' . $item->slug()->value() . '.json';

        $this->writeJson($path, $snapshot);

        return $path;
    }

    /**
     * @param iterable<ContentItem> $items
     * @return list<string>
     */
    public function exportAll(iterable $items): array
    {
        $paths = [];

        foreach ($items as $item) {
            $paths[] = $this->exportContentItem($item);
        }

        return $paths;
    }

    public function exportSystemRoutes(): string
    {
        $this->ensureExportDirectoryExists();

        $path = $this->exportDirectory() . '/system-routes.json';
        $payload = [
            'kind' => 'blueprint-composition-snapshot',
            'scope' => 'system-routes',
            'export_format_version' => 2,
            'note' => 'Blueprint-specific route composition metadata for AI/tooling. Separate from OCF.',
            'routes' => $this->systemRouteComposition,
        ];

        $this->writeJson($path, $payload);

        return $path;
    }

    private function ensureExportDirectoryExists(): void
    {
        $directory = $this->exportDirectory();

        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Failed to create composition export directory: %s', $directory));
        }
    }

    private function exportDirectory(): string
    {
        return rtrim($this->projectRoot, '/\\') . '/storage/exports/composition';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeJson(string $path, array $payload): void
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $encoded . PHP_EOL) === false) {
            throw new RuntimeException(sprintf('Failed to write composition snapshot: %s', $path));
        }
    }
}
