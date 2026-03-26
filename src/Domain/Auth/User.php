<?php

declare(strict_types=1);

namespace App\Domain\Auth;

final class User
{
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly string $displayName,
        private readonly Role $role,
        private readonly bool $active
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
