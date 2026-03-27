<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Infrastructure\Database\Connection;
use Throwable;

final class UpgradeState
{
    public function __construct(
        private readonly AppVersion $appVersion,
        private readonly ?Connection $connection
    ) {
    }

    public function isUpgradeRequired(): bool
    {
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
        if ($this->connection === null) {
            return null;
        }

        try {
            $result = $this->connection->fetchOne(
                <<<'SQL'
SELECT installed_version
FROM settings
ORDER BY id ASC
LIMIT 1
SQL
            );
        } catch (Throwable) {
            return null;
        }

        if ($result === null || !array_key_exists('installed_version', $result)) {
            return null;
        }

        $installedVersion = $result['installed_version'];

        if (!is_string($installedVersion)) {
            return null;
        }

        $trimmedVersion = trim($installedVersion);

        return $trimmedVersion === '' ? null : $trimmedVersion;
    }
}
