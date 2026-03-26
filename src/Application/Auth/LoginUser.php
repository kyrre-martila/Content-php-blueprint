<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Auth\User;
use App\Infrastructure\Auth\AuthSession;

final class LoginUser
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AuthSession $authSession
    ) {
    }

    public function execute(string $email, string $password): bool
    {
        $candidateEmail = mb_strtolower(trim($email));

        if ($candidateEmail === '' || $password === '') {
            return false;
        }

        $user = $this->users->findByEmail($candidateEmail);

        if (!$user instanceof User || !$user->isActive()) {
            return false;
        }

        if (!password_verify($password, $user->passwordHash())) {
            return false;
        }

        $this->authSession->login($user);

        return true;
    }
}
