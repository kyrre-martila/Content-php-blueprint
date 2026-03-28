<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Infrastructure\Database\Connection;
use Throwable;

final class UpgradeState
{
    /** @var array<string, mixed>|null */
    private ?array $settingsRecord = null;

    private bool $settingsLoaded = false;

    public function __construct(
        private readonly AppVersion $appVersion,
        private readonly ?Connection $connection
    ) {
    }

    public function isUpgradeRequired(): bool
    {
        if (!$this->isInstallCompleted()) {
            return false;
        }

        $installedVersion = $this->installedVersion();

        if ($installedVersion === null || $installedVersion === '') {
            return false;
        }

        return version_compare($this->currentVersion(), $installedVersion, '>');
    }

    public function currentVersion(): string
    {
        return $this->appVersion->currentVersion();
    }

    public function installedVersion(): ?string
    {
        $record = $this->settingsRecord();

        if ($record === null || !array_key_exists('installed_version', $record)) {
            return null;
        }

        $installedVersion = $record['installed_version'];

        if (!is_string($installedVersion)) {
            return null;
        }

        $trimmedVersion = trim($installedVersion);

        return $trimmedVersion === '' ? null : $trimmedVersion;
    }

    public function hasSettingsRecord(): bool
    {
        return $this->settingsRecord() !== null;
    }

    public function isInstallCompleted(): bool
    {
        $record = $this->settingsRecord();

        if ($record === null || !array_key_exists('install_completed', $record)) {
            return false;
        }

        $value = $record['install_completed'];

        return match (true) {
            is_bool($value) => $value,
            is_int($value) => $value === 1,
            is_string($value) => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            default => false,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function settingsRecord(): ?array
    {
        if ($this->settingsLoaded) {
            return $this->settingsRecord;
        }

        $this->settingsLoaded = true;

        if ($this->connection === null) {
            return null;
        }

        try {
            $result = $this->connection->fetchOne(
                <<<'SQL'
SELECT install_completed, installed_version
FROM settings
ORDER BY id ASC
LIMIT 1
SQL
            );
        } catch (Throwable) {
            return null;
        }

        $this->settingsRecord = $result;

        return $this->settingsRecord;
    }
}
