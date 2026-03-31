<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use RuntimeException;

final class RuntimeStorage
{
    /**
     * @return list<string>
     */
    public static function requiredDirectories(string $projectRoot): array
    {
        $storagePath = rtrim($projectRoot, '/\\') . '/storage';

        return [
            $storagePath,
            $storagePath . '/logs',
            $storagePath . '/exports',
            $storagePath . '/exports/composition',
            $storagePath . '/exports/ocf',
        ];
    }

    public static function ensure(string $projectRoot): void
    {
        foreach (self::requiredDirectories($projectRoot) as $directory) {
            if (is_dir($directory)) {
                continue;
            }

            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Unable to create runtime directory: %s', $directory));
            }
        }
    }
}
