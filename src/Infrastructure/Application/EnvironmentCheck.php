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
        RuntimeStorage::ensure($this->projectRoot);

        $storagePath = $this->projectRoot . '/storage';
        $envPath = $this->projectRoot . '/.env';

        return [
            'php_version' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'storage_writable' => is_dir($storagePath) && is_writable($storagePath),
            'env_writable' => !is_file($envPath) || is_writable($envPath),
        ];
    }
}
