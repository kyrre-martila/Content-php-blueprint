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
        if (!$this->canUse()) {
            return false;
        }

        return $this->session->get(self::SESSION_KEY, false) === true;
    }

    public function canUse(): bool
    {
        $role = $this->authSession->role();

        if ($role === null) {
            return false;
        }

        return $role->equals(Role::superadmin())
            || $role->equals(Role::admin())
            || $role->equals(Role::editor());
    }

    public function enable(): void
    {
        if (!$this->canUse()) {
            return;
        }

        $this->session->set(self::SESSION_KEY, true);
    }

    public function disable(): void
    {
        $this->session->set(self::SESSION_KEY, false);
    }
}
