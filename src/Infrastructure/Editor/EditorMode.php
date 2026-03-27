<?php

declare(strict_types=1);

namespace App\Infrastructure\Editor;

use App\Domain\Auth\Role;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;

final class EditorMode
{
    private const SESSION_KEY = 'editor_mode.active';

    public function __construct(
        private readonly AuthSession $authSession,
        private readonly SessionManager $session
    ) {
    }

    public function isActive(): bool
    {
        if (!$this->canEdit()) {
            return false;
        }

        return $this->session->get(self::SESSION_KEY, false) === true;
    }

    public function canEdit(): bool
    {
        $user = $this->authSession->user();

        if (!is_array($user)) {
            return false;
        }

        $role = $user['role'] ?? null;

        if (!is_string($role)) {
            return false;
        }

        return in_array($role, [Role::SUPERADMIN, Role::ADMIN, Role::EDITOR], true);
    }

    public function enable(): void
    {
        if (!$this->canEdit()) {
            return;
        }

        $this->session->set(self::SESSION_KEY, true);
    }

    public function disable(): void
    {
        $this->session->set(self::SESSION_KEY, false);
    }
}
