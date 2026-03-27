<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Infrastructure\Database\Connection;
use Throwable;

final class InstallState
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $migrationsTable = 'phinxlog'
    ) {
    }

    public function isInstalled(): bool
    {
        return $this->requiredTablesExist()
            && $this->adminUserExists()
            && $this->installFlagIsTrue();
    }

    private function requiredTablesExist(): bool
    {
        return $this->tableExists('users')
            && $this->tableExists('content_types')
            && $this->tableExists('settings');
    }

    private function adminUserExists(): bool
    {
        try {
            $result = $this->connection->fetchOne(
                <<<'SQL'
SELECT COUNT(*) AS admin_count
FROM users u
INNER JOIN roles r ON r.id = u.role_id
WHERE r.slug IN ('admin', 'superadmin')
LIMIT 1
SQL
            );
        } catch (Throwable) {
            return false;
        }

        $adminCount = $result['admin_count'] ?? 0;

        if (is_int($adminCount)) {
            return $adminCount > 0;
        }

        return is_string($adminCount) && ctype_digit($adminCount) && (int) $adminCount > 0;
    }

    private function installFlagIsTrue(): bool
    {
        try {
            $result = $this->connection->fetchOne(
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

    private function tableExists(string $table): bool
    {
        try {
            $result = $this->connection->fetchOne(
                <<<'SQL'
SELECT COUNT(*) AS table_count
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = :table
LIMIT 1
SQL,
                ['table' => $table]
            );
        } catch (Throwable) {
            return false;
        }

        $tableCount = $result['table_count'] ?? 0;

        if (is_int($tableCount)) {
            return $tableCount > 0;
        }

        return is_string($tableCount) && ctype_digit($tableCount) && (int) $tableCount > 0;
    }
}
