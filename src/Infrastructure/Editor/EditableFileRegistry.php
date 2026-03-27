<?php

declare(strict_types=1);

namespace App\Infrastructure\Editor;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class EditableFileRegistry
{
    /**
     * @var array<string, array<int, string>>
     */
    private const EXTENSIONS_BY_AREA = [
        'templates' => ['php'],
        'patterns' => ['php', 'json'],
        'public/assets/css' => ['css'],
        'public/assets/js' => ['js'],
    ];

    public function __construct(
        private readonly string $projectRoot,
        private readonly DevMode $devMode
    ) {
    }

    /**
     * @return array<string, list<string>>
     */
    public function discover(): array
    {
        $grouped = [];

        foreach ($this->rootsByArea() as $area => $root) {
            $grouped[$area] = $this->discoverInRoot($root);
        }

        return $grouped;
    }

    public function isSupportedPath(string $relativePath): bool
    {
        $normalized = $this->normalizeRelativePath($relativePath);

        if ($normalized === null) {
            return false;
        }

        foreach (self::EXTENSIONS_BY_AREA as $area => $extensions) {
            $prefix = $area . '/';

            if (!str_starts_with($normalized, $prefix)) {
                continue;
            }

            $extension = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));

            return in_array($extension, $extensions, true);
        }

        return false;
    }

    public function absolutePath(string $relativePath): ?string
    {
        $normalized = $this->normalizeRelativePath($relativePath);

        if ($normalized === null) {
            return null;
        }

        $absolute = rtrim($this->projectRoot, '/\\') . '/' . $normalized;

        if (!$this->devMode->isAllowedPath($absolute)) {
            return null;
        }

        return $absolute;
    }

    /**
     * @return array<string, string>
     */
    private function rootsByArea(): array
    {
        return [
            'templates' => rtrim($this->projectRoot, '/\\') . '/templates',
            'patterns' => rtrim($this->projectRoot, '/\\') . '/patterns',
            'public/assets/css' => rtrim($this->projectRoot, '/\\') . '/public/assets/css',
            'public/assets/js' => rtrim($this->projectRoot, '/\\') . '/public/assets/js',
        ];
    }

    /**
     * @return list<string>
     */
    private function discoverInRoot(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $absolutePath = $fileInfo->getPathname();

            if (!$this->devMode->isAllowedPath($absolutePath) || $this->containsHiddenSegment($absolutePath)) {
                continue;
            }

            $relativePath = $this->toRelativePath($absolutePath);

            if ($relativePath === null || !$this->isSupportedPath($relativePath)) {
                continue;
            }

            $files[] = $relativePath;
        }

        sort($files);

        return $files;
    }

    private function toRelativePath(string $absolutePath): ?string
    {
        $prefix = rtrim(str_replace('\\', '/', $this->projectRoot), '/') . '/';
        $normalized = str_replace('\\', '/', $absolutePath);

        if (!str_starts_with($normalized, $prefix)) {
            return null;
        }

        return substr($normalized, strlen($prefix));
    }

    private function containsHiddenSegment(string $path): bool
    {
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment !== '' && str_starts_with($segment, '.')) {
                return true;
            }
        }

        return false;
    }

    private function normalizeRelativePath(string $relativePath): ?string
    {
        if ($relativePath === '' || str_contains($relativePath, "\0")) {
            return null;
        }

        $trimmed = trim(str_replace('\\', '/', $relativePath), '/');

        if ($trimmed === '') {
            return null;
        }

        if (str_contains($trimmed, '../') || str_starts_with($trimmed, '../') || str_contains($trimmed, '/..')) {
            return null;
        }

        return $trimmed;
    }
}
