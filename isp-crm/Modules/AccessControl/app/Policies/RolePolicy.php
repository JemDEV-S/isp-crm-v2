<?php

declare(strict_types=1);

namespace Modules\AccessControl\Policies;

use Modules\AccessControl\Entities\Role;
use Modules\AccessControl\Entities\User;

class RolePolicy
{
    /**
     * Determine if the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('accesscontrol.role.view');
    }

    /**
     * Determine if the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission('accesscontrol.role.view');
    }

    /**
     * Determine if the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('accesscontrol.role.create');
    }

    /**
     * Determine if the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        // Los roles del sistema solo pueden ser editados por superadmin
        if ($role->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('accesscontrol.role.update');
    }

    /**
     * Determine if the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        // Los roles del sistema no pueden ser eliminados
        if ($role->is_system) {
            return false;
        }

        return $user->hasPermission('accesscontrol.role.delete');
    }

    /**
     * Determine if the user can assign permissions to the role.
     */
    public function assignPermissions(User $user, Role $role): bool
    {
        // Los roles del sistema solo pueden ser editados por superadmin
        if ($role->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('accesscontrol.permission.assign');
    }
}
