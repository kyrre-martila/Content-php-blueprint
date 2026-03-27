<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Infrastructure\Config\ConfigRepository;

final class AppVersion
{
    public function __construct(private readonly ConfigRepository $config)
    {
    }

    public function currentVersion(): string
    {
        $configuredVersion = $this->config->get('app.version', '0.1.0');

        return is_string($configuredVersion) && $configuredVersion !== ''
            ? $configuredVersion
            : '0.1.0';
    }

    public function applicationName(): string
    {
        $configuredName = $this->config->get('app.name', 'Content PHP Blueprint');

        return is_string($configuredName) && $configuredName !== ''
            ? $configuredName
            : 'Content PHP Blueprint';
    }
}
