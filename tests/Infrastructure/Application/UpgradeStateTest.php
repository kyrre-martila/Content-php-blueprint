<?php

declare(strict_types=1);

use App\Infrastructure\Application\AppVersion;
use App\Infrastructure\Application\UpgradeState;
use App\Infrastructure\Config\ConfigRepository;
use App\Infrastructure\Database\Connection;

it('reports upgrade required when current version is newer than installed version', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE settings (id INTEGER PRIMARY KEY, install_completed INTEGER NOT NULL DEFAULT 0, installed_version TEXT NULL)');
    $pdo->exec("INSERT INTO settings (id, install_completed, installed_version) VALUES (1, 1, '0.9.0')");

    $upgradeState = new UpgradeState(
        new AppVersion(new ConfigRepository(['app' => ['version' => '1.0.0']])),
        new Connection($pdo)
    );

    expect($upgradeState->installedVersion())->toBe('0.9.0')
        ->and($upgradeState->isInstallCompleted())->toBeTrue()
        ->and($upgradeState->isUpgradeRequired())->toBeTrue();
});

it('does not require upgrade when installed version matches current version', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE settings (id INTEGER PRIMARY KEY, install_completed INTEGER NOT NULL DEFAULT 0, installed_version TEXT NULL)');
    $pdo->exec("INSERT INTO settings (id, install_completed, installed_version) VALUES (1, 1, '1.0.0')");

    $upgradeState = new UpgradeState(
        new AppVersion(new ConfigRepository(['app' => ['version' => '1.0.0']])),
        new Connection($pdo)
    );

    expect($upgradeState->isUpgradeRequired())->toBeFalse();
});

it('returns null installed version when persistence is unavailable', function (): void {
    $upgradeState = new UpgradeState(
        new AppVersion(new ConfigRepository(['app' => ['version' => '1.0.0']])),
        null
    );

    expect($upgradeState->installedVersion())->toBeNull()
        ->and($upgradeState->hasSettingsRecord())->toBeFalse()
        ->and($upgradeState->isInstallCompleted())->toBeFalse()
        ->and($upgradeState->isUpgradeRequired())->toBeFalse();
});

it('treats missing settings row as installation incomplete', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE settings (id INTEGER PRIMARY KEY, install_completed INTEGER NOT NULL DEFAULT 0, installed_version TEXT NULL)');

    $upgradeState = new UpgradeState(
        new AppVersion(new ConfigRepository(['app' => ['version' => '1.0.0']])),
        new Connection($pdo)
    );

    expect($upgradeState->hasSettingsRecord())->toBeFalse()
        ->and($upgradeState->isInstallCompleted())->toBeFalse()
        ->and($upgradeState->installedVersion())->toBeNull()
        ->and($upgradeState->isUpgradeRequired())->toBeFalse();
});
