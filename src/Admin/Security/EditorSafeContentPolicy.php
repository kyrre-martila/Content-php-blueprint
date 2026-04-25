<?php

declare(strict_types=1);

namespace App\Admin\Security;

use App\Domain\Auth\Role;

final class EditorSafeContentPolicy
{
    public function canCreateContent(Role $role): bool
    {
        return !$role->equals(Role::editor());
    }

    public function canDeleteContent(Role $role): bool
    {
        return !$role->equals(Role::editor());
    }

    public function canEditSlug(Role $role): bool
    {
        return true;
    }

    public function canEditStatus(Role $role): bool
    {
        return !$role->equals(Role::editor());
    }

    public function canChangeContentType(Role $role): bool
    {
        return !$role->equals(Role::editor());
    }

    public function canEditSeoMetadata(Role $role): bool
    {
        return !$role->equals(Role::editor());
    }

    public function canEditPatternBlocks(Role $role): bool
    {
        return !$role->equals(Role::editor());
    }

    public function canAssignCategories(Role $role): bool
    {
        return !$role->equals(Role::editor());
    }
}
