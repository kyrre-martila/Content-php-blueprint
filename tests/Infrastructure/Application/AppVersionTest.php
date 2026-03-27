<?php

declare(strict_types=1);

use App\Infrastructure\Application\AppVersion;
use App\Infrastructure\Config\ConfigRepository;

it('returns application name and version from config', function (): void {
    $service = new AppVersion(new ConfigRepository([
        'app' => [
            'name' => 'Blueprint QA',
            'version' => '1.2.3',
        ],
    ]));

    expect($service->applicationName())->toBe('Blueprint QA')
        ->and($service->currentVersion())->toBe('1.2.3');
});

it('falls back to defaults when config values are missing', function (): void {
    $service = new AppVersion(new ConfigRepository([]));

    expect($service->applicationName())->toBe('Content PHP Blueprint')
        ->and($service->currentVersion())->toBe('0.1.0');
});
