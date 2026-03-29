<?php

declare(strict_types=1);

namespace App\Application\DevMode;

use RuntimeException;

final class DevFileService
{
    /**
     * @var list<string>
     */
    private const ALLOWED_DIRECTORIES = [
        'templates',
        'patterns',
        'public/assets/css',
        'public/assets/js',
    ];

    /**
     * @var list<string>
     */
    private const BLOCKED_PATHS = [
        'src',
        'vendor',
        '.env',
        'composer.json',
        'composer.lock',
        'storage',
        'config',
    ];

    public function __construct(
        private readonly string $projectRoot
    ) {
    }

    public function safeReadFile(string $path): string
    {
        $absolutePath = $this->resolveEditableFilePath($path);

        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            throw new RuntimeException('Requested file is not readable.');
        }

        $contents = file_get_contents($absolutePath);

        if (!is_string($contents)) {
            throw new RuntimeException('Failed to read file contents.');
        }

        return $contents;
    }

    public function safeWriteFile(string $path, string $contents): void
    {
        $absolutePath = $this->resolveEditableFilePath($path);

        if (!is_file($absolutePath) || !is_writable($absolutePath)) {
            throw new RuntimeException('Requested file is not writable.');
        }

        $this->writeAtomically($absolutePath, $contents);
    }

    public function safeCreateFile(string $path, string $contents): void
    {
        $absolutePath = $this->resolveEditableFilePath($path);

        if (is_file($absolutePath)) {
            throw new RuntimeException('Requested file already exists.');
        }

        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create target directory.');
        }

        if (!is_writable($directory)) {
            throw new RuntimeException('Target directory is not writable.');
        }

        $this->writeAtomically($absolutePath, $contents);
    }

    public function isAllowedPath(string $path): bool
    {
        try {
            $this->resolveEditableFilePath($path);
        } catch (RuntimeException) {
            return false;
        }

        return true;
    }

    public function absolutePath(string $path): string
    {
        return $this->resolveEditableFilePath($path);
    }

    private function resolveEditableFilePath(string $path): string
    {
        $normalized = $this->normalizeRelativePath($path);

        if ($normalized === null) {
            throw new RuntimeException('Invalid file path.');
        }

        if ($this->isBlockedPath($normalized)) {
            throw new RuntimeException('Editing this path is not permitted.');
        }

        if (!$this->isInsideAllowedDirectories($normalized)) {
            throw new RuntimeException('Path is outside the editable development directories.');
        }

        return rtrim($this->projectRoot, '/\\') . '/' . $normalized;
    }

    private function normalizeRelativePath(string $path): ?string
    {
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        $trimmed = trim(str_replace('\\', '/', $path), '/');

        if ($trimmed === '') {
            return null;
        }

        $segments = explode('/', $trimmed);
        $normalizedSegments = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                return null;
            }

            $normalizedSegments[] = $segment;
        }

        if ($normalizedSegments === []) {
            return null;
        }

        return implode('/', $normalizedSegments);
    }

    private function isInsideAllowedDirectories(string $normalizedRelativePath): bool
    {
        foreach (self::ALLOWED_DIRECTORIES as $allowedDirectory) {
            if (
                $normalizedRelativePath === $allowedDirectory
                || str_starts_with($normalizedRelativePath, $allowedDirectory . '/')
            ) {
                return true;
            }
        }

        return false;
    }

    private function isBlockedPath(string $normalizedRelativePath): bool
    {
        foreach (self::BLOCKED_PATHS as $blockedPath) {
            if (
                $normalizedRelativePath === $blockedPath
                || str_starts_with($normalizedRelativePath, $blockedPath . '/')
            ) {
                return true;
            }
        }

        return false;
    }

    private function writeAtomically(string $absolutePath, string $content): void
    {
        $directory = dirname($absolutePath);
        $tempPath = sprintf('%s/.devmode-%s.tmp', $directory, uniqid('', true));

        $written = file_put_contents($tempPath, $content, LOCK_EX);

        if ($written === false) {
            throw new RuntimeException('Failed writing temporary file.');
        }

        $renamed = rename($tempPath, $absolutePath);

        if (!$renamed) {
            @unlink($tempPath);
            throw new RuntimeException('Failed replacing target file.');
        }
    }
}
