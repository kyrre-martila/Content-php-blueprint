<?php

declare(strict_types=1);

namespace App\Infrastructure\Editor;

use App\Domain\Auth\Role;
use App\Infrastructure\Auth\AuthSession;
use App\Infrastructure\Auth\SessionManager;

final class DevMode
{
    private const SESSION_KEY = 'dev_mode.active';

    public function __construct(
        private readonly string $projectRoot,
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
        $user = $this->authSession->user();

        if (!is_array($user)) {
            return false;
        }

        $role = $user['role'] ?? null;

        if (!is_string($role)) {
            return false;
        }

        return in_array($role, [Role::SUPERADMIN, Role::ADMIN], true);
    }

    /**
     * Dev Mode is intentionally scoped to presentation-layer resources only.
     *
     * Allowed roots (v1):
     * - templates/
     * - patterns/
     * - public/assets/css/
     * - public/assets/js/
     *
     * Explicitly excluded examples:
     * - src/Domain/, src/Application/, src/Infrastructure/Database/
     * - auth internals, migrations, configuration secrets, .env, vendor/
     *
     * @return array<int, string>
     */
    public function allowedRoots(): array
    {
        return [
            $this->absolutePath('templates'),
            $this->absolutePath('patterns'),
            $this->absolutePath('public/assets/css'),
            $this->absolutePath('public/assets/js'),
        ];
    }

    public function isAllowedPath(string $path): bool
    {
        $absolutePath = $this->normalizePath($path);

        if ($absolutePath === null) {
            return false;
        }

        foreach ($this->allowedRoots() as $allowedRoot) {
            $normalizedRoot = rtrim(str_replace('\\', '/', $allowedRoot), '/');
            if ($absolutePath === $normalizedRoot || str_starts_with($absolutePath, $normalizedRoot . '/')) {
                return true;
            }
        }

        return false;
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

    private function absolutePath(string $relativePath): string
    {
        return rtrim($this->projectRoot, '/\\') . '/' . ltrim($relativePath, '/\\');
    }

    private function normalizePath(string $path): ?string
    {
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        $candidate = $path;

        if (!str_starts_with($candidate, '/')) {
            $candidate = $this->absolutePath($candidate);
        }

        $resolved = realpath($candidate);

        if ($resolved === false) {
            return null;
        }

        return str_replace('\\', '/', $resolved);
    }
}
