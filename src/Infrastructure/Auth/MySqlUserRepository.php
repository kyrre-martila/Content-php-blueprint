<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Auth\Role;
use App\Domain\Auth\User;
use App\Infrastructure\Database\Connection;
use RuntimeException;

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


    public function createInitialAdmin(string $email, string $plainPassword): void
    {
        $normalizedEmail = mb_strtolower(trim($email));
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

        if (!is_string($passwordHash)) {
            throw new RuntimeException('Failed to hash admin password.');
        }

        $this->connection->transaction(function (Connection $connection) use ($normalizedEmail, $passwordHash): void {
            $connection->execute(
                <<<'SQL'
INSERT INTO roles (name, slug, description)
VALUES (:name, :slug, :description)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description)
SQL,
                [
                    'name' => 'Admin',
                    'slug' => Role::admin()->value(),
                    'description' => 'Administrative access for site management',
                ]
            );

            $roleRow = $connection->fetchOne(
                <<<'SQL'
SELECT id
FROM roles
WHERE slug = :slug
LIMIT 1
SQL,
                ['slug' => Role::admin()->value()]
            );

            if ($roleRow === null) {
                throw new RuntimeException('Admin role could not be created.');
            }

            $roleId = $roleRow['id'] ?? null;

            if (!is_int($roleId) && !(is_string($roleId) && ctype_digit($roleId))) {
                throw new RuntimeException('Admin role id is invalid.');
            }

            $connection->execute(
                <<<'SQL'
INSERT INTO users (role_id, email, password_hash, display_name, is_active)
VALUES (:role_id, :email, :password_hash, :display_name, :is_active)
ON DUPLICATE KEY UPDATE
    role_id = VALUES(role_id),
    password_hash = VALUES(password_hash),
    display_name = VALUES(display_name),
    is_active = VALUES(is_active)
SQL,
                [
                    'role_id' => (int) $roleId,
                    'email' => $normalizedEmail,
                    'password_hash' => $passwordHash,
                    'display_name' => 'Initial Admin',
                    'is_active' => true,
                ]
            );
        });
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapUser(array $row): User
    {
        $roleSlug = is_string($row['role_slug'] ?? null) ? $row['role_slug'] : Role::editor()->value();

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
