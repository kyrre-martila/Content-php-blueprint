<?php

declare(strict_types=1);

namespace App\Infrastructure\Files;

use RuntimeException;

final class LocalFileStorage implements FileStorageInterface
{
    public function __construct(private readonly string $rootPath)
    {
    }

    public function write(string $storagePath, string $contents): void
    {
        $absolutePath = $this->absolutePath($storagePath);
        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory for file storage: %s', $directory));
        }

        if (file_put_contents($absolutePath, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write file to storage path: %s', $storagePath));
        }
    }

    public function read(string $storagePath): string
    {
        $absolutePath = $this->absolutePath($storagePath);
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read file from storage path: %s', $storagePath));
        }

        return $contents;
    }

    public function exists(string $storagePath): bool
    {
        return is_file($this->absolutePath($storagePath));
    }

    public function absolutePath(string $storagePath): string
    {
        $normalizedPath = $this->normalizePath($storagePath);

        return rtrim($this->rootPath, '/\\') . '/' . $normalizedPath;
    }

    private function normalizePath(string $storagePath): string
    {
        $trimmed = trim($storagePath);
        if ($trimmed === '') {
            throw new RuntimeException('Storage path cannot be empty.');
        }

        $normalized = str_replace('\\', '/', $trimmed);
        if (str_starts_with($normalized, '/')) {
            throw new RuntimeException('Storage path must be relative.');
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('Storage path contains invalid segments.');
            }
        }

        return $normalized;
    }
}
