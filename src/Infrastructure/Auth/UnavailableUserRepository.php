<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Auth\User;

/**
 * Login is disabled while persistence is unavailable.
 *
 * We return null users instead of creating a fake PDO-backed repository.
 */
final class UnavailableUserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return null;
    }

    public function findById(int $id): ?User
    {
        return null;
    }
}
