<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\User;

final class AuthSession
{
    private const SESSION_KEY = 'auth.user';

    public function __construct(private readonly SessionManager $session)
    {
    }

    public function login(User $user): void
    {
        $this->session->regenerateId();
        $this->session->set(self::SESSION_KEY, [
            'id' => $user->id(),
            'email' => $user->email(),
            'display_name' => $user->displayName(),
            'role' => $user->role()->value(),
        ]);
    }

    public function logout(): void
    {
        $this->session->invalidate();
    }

    public function isAuthenticated(): bool
    {
        $user = $this->session->get(self::SESSION_KEY);

        return is_array($user) && isset($user['id']) && is_int($user['id']);
    }

    /**
     * @return array{id:int,email:string,display_name:string,role:string}|null
     */
    public function user(): ?array
    {
        $user = $this->session->get(self::SESSION_KEY);

        if (!is_array($user)) {
            return null;
        }

        if (!isset($user['id'], $user['email'], $user['display_name'], $user['role'])) {
            return null;
        }

        if (!is_int($user['id']) || !is_string($user['email']) || !is_string($user['display_name']) || !is_string($user['role'])) {
            return null;
        }

        return $user;
    }
}
