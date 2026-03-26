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
        return $this->tableExists($this->migrationsTable)
            && $this->tableExists('users')
            && $this->tableExists('content_types');
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

        return (int) ($result['table_count'] ?? 0) > 0;
    }
}
