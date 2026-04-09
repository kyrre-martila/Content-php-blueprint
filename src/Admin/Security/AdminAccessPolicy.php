<?php

declare(strict_types=1);

namespace App\Admin\Security;

use App\Domain\Auth\Role;

final class AdminAccessPolicy
{
    /**
     * @return list<Role>
     */
    public function allowedRolesForPath(string $path): array
    {
        if ($this->isEditorSafePath($path)) {
            return [
                Role::editor(),
                Role::admin(),
                Role::superadmin(),
            ];
        }

        return [
            Role::admin(),
            Role::superadmin(),
        ];
    }

    public function canAccessPath(Role $role, string $path): bool
    {
        foreach ($this->allowedRolesForPath($path) as $allowedRole) {
            if ($role->equals($allowedRole)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessContentManagement(Role $role): bool
    {
        return $this->canAccessPath($role, '/admin/content');
    }

    public function canAccessFileLibrary(Role $role): bool
    {
        return $this->canAccessPath($role, '/admin/files');
    }

    public function canAccessSystemManagement(Role $role): bool
    {
        return $this->canAccessPath($role, '/admin/content-types');
    }

    private function isEditorSafePath(string $path): bool
    {
        return $path === '/admin'
            || $path === '/admin/content'
            || str_starts_with($path, '/admin/content/')
            || $path === '/admin/files'
            || str_starts_with($path, '/admin/files/');
    }
}

