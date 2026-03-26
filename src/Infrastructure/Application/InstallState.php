<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use App\Infrastructure\Database\Connection;
use Throwable;

final class InstallState
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function isInstalled(): bool
    {
        try {
            $result = $this->connection->fetchOne(
                <<<'SQL'
SELECT COUNT(*) AS admin_count
FROM users
WHERE email = :email
LIMIT 1
SQL,
                ['email' => 'admin@example.com']
            );
        } catch (Throwable) {
            return false;
        }

        $adminCount = (int) ($result['admin_count'] ?? 0);

        return $adminCount > 0;
    }
}
