<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Auth\Role;
use App\Domain\Auth\User;
use App\Infrastructure\Database\Connection;

final class MySqlUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->connection->fetchOne(
            <<<'SQL'
SELECT
    u.id,
    u.email,
    u.password_hash,
    u.display_name,
    u.is_active,
    r.slug AS role_slug
FROM users u
LEFT JOIN roles r ON r.id = u.role_id
WHERE u.email = :email
LIMIT 1
SQL,
            ['email' => mb_strtolower(trim($email))]
        );

        return $row === null ? null : $this->mapUser($row);
    }

    public function findById(int $id): ?User
    {
        $row = $this->connection->fetchOne(
            <<<'SQL'
SELECT
    u.id,
    u.email,
    u.password_hash,
    u.display_name,
    u.is_active,
    r.slug AS role_slug
FROM users u
LEFT JOIN roles r ON r.id = u.role_id
WHERE u.id = :id
LIMIT 1
SQL,
            ['id' => $id]
        );

        return $row === null ? null : $this->mapUser($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapUser(array $row): User
    {
        $roleSlug = is_string($row['role_slug'] ?? null) ? $row['role_slug'] : Role::EDITOR;

        return new User(
            (int) ($row['id'] ?? 0),
            (string) ($row['email'] ?? ''),
            (string) ($row['password_hash'] ?? ''),
            (string) ($row['display_name'] ?? ''),
            Role::fromString($roleSlug),
            (bool) ($row['is_active'] ?? false)
        );
    }
}
