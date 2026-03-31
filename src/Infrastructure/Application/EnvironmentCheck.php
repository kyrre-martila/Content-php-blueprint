<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

final class EnvironmentCheck
{
    public function __construct(private readonly string $projectRoot)
    {
    }

    /**
     * @return array<string, bool>
     */
    public function run(): array
    {
        $envPath = $this->projectRoot . '/.env';
        $requiredDirectories = RuntimeStorage::requiredDirectories($this->projectRoot);

        $storageWritable = true;

        foreach ($requiredDirectories as $directory) {
            if (!is_dir($directory) || !is_writable($directory)) {
                $storageWritable = false;
                break;
            }
        }

        return [
            'php_version' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'storage_writable' => $storageWritable,
            'env_writable' => !is_file($envPath) || is_writable($envPath),
        ];
    }
}
