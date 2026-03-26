<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use RuntimeException;

final class ConfigLoader
{
    public function __construct(private readonly string $configPath)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function load(): array
    {
        if (!is_dir($this->configPath)) {
            throw new RuntimeException(sprintf('Config directory not found: %s', $this->configPath));
        }

        $files = glob($this->configPath . '/*.php');

        if ($files === false) {
            throw new RuntimeException(sprintf('Unable to read config directory: %s', $this->configPath));
        }

        sort($files);

        $config = [];

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $data = require $file;

            if (!is_array($data)) {
                throw new RuntimeException(sprintf('Config file must return an array: %s', $file));
            }

            $config[$key] = $data;
        }

        return $config;
    }
}
