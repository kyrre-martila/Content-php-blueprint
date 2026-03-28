<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Infrastructure\Database\Connection;
use App\Domain\Logging\LoggerInterface;
use Throwable;

final class UpgradeRunner
{
    public function __construct(
        private readonly UpgradeState $upgradeState,
        private readonly ?Connection $connection,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function runUpgradeIfNeeded(): void
    {
        if ($this->connection === null) {
            $this->logInfo('Upgrade check skipped because database connection is unavailable.');

            return;
        }

        if (!$this->isInstallCompleted()) {
            $this->logInfo('Upgrade check skipped because installation is not completed yet.');

            return;
        }

        if (!$this->upgradeState->isUpgradeRequired()) {
            $this->logInfo('Upgrade check completed; versions already match or no upgrade is required.', [
                'current_version' => $this->upgradeState->currentVersion(),
                'installed_version' => $this->upgradeState->installedVersion(),
            ]);

            return;
        }

        $this->logInfo('Version mismatch detected; starting upgrade runner.', [
            'current_version' => $this->upgradeState->currentVersion(),
            'installed_version' => $this->upgradeState->installedVersion(),
        ]);

        try {
            $this->runUpgradeTasks();
            $this->persistInstalledVersion($this->upgradeState->currentVersion());

            $this->logInfo('Upgrade runner finished successfully.', [
                'installed_version' => $this->upgradeState->currentVersion(),
            ]);
        } catch (Throwable $throwable) {
            $this->logWarning('Upgrade runner encountered an error and exited safely.', [
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    public function runUpgradeTasks(): void
    {
        // Future integration point: run pending DB migrations for upgraded release.
        $this->logInfo('Upgrade task placeholder: migration execution hook is ready.');

        // Future integration point: invalidate runtime caches after schema/data updates.
        $this->logInfo('Upgrade task placeholder: cache invalidation hook is ready.');

        // Future integration point: rebuild composition snapshot after upgraded files are deployed.
        $this->logInfo('Upgrade task placeholder: composition snapshot refresh hook is ready.');

        // Future integration point: regenerate export artifacts after upgraded transformations.
        $this->logInfo('Upgrade task placeholder: export regeneration hook is ready.');

        // Future integration point: execute any additional schema/data maintenance routines.
        $this->logInfo('Upgrade task placeholder: future schema upgrade hook is ready.');
    }

    private function isInstallCompleted(): bool
    {
        if (!$this->settingsTableExists()) {
            return false;
        }

        try {
            $result = $this->connection?->fetchOne(
                <<<'SQL'
SELECT install_completed
FROM settings
ORDER BY id ASC
LIMIT 1
SQL
            );
        } catch (Throwable) {
            return false;
        }

        if ($result === null || !array_key_exists('install_completed', $result)) {
            return false;
        }

        $value = $result['install_completed'];

        return match (true) {
            is_bool($value) => $value,
            is_int($value) => $value === 1,
            is_string($value) => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            default => false,
        };
    }

    private function settingsTableExists(): bool
    {
        try {
            $result = $this->connection?->fetchOne(
                <<<'SQL'
SELECT COUNT(*) AS table_count
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = 'settings'
LIMIT 1
SQL
            );
        } catch (Throwable) {
            return false;
        }

        if ($result === null || !array_key_exists('table_count', $result)) {
            return false;
        }

        $tableCount = $result['table_count'];

        if (is_int($tableCount)) {
            return $tableCount > 0;
        }

        return is_string($tableCount) && ctype_digit($tableCount) && (int) $tableCount > 0;
    }

    private function persistInstalledVersion(string $version): void
    {
        if ($this->connection === null) {
            return;
        }

        $this->connection->execute(
            <<<'SQL'
UPDATE settings
SET installed_version = :version,
    install_completed = 1,
    updated_at = CURRENT_TIMESTAMP
ORDER BY id ASC
LIMIT 1
SQL,
            ['version' => $version]
        );
    }

    /**
     * @param array<string, scalar|null> $context
     */
    private function logInfo(string $message, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->info($message, $context, 'upgrade');
    }

    /**
     * @param array<string, scalar|null> $context
     */
    private function logWarning(string $message, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->warning($message, $context, 'upgrade');
    }
}
