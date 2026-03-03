<?php

declare(strict_types=1);

namespace Modules\AccessControl\Policies;

use Modules\AccessControl\Entities\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'accesscontrol.user.view',
            'accesscontrol.user.view.zone',
        ]);
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // SuperAdmin puede ver todos
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Si tiene permiso general
        if ($user->hasPermission('accesscontrol.user.view')) {
            return true;
        }

        // Si tiene permiso por zona
        if ($user->hasPermission('accesscontrol.user.view.zone')) {
            return $user->zone_id === $model->zone_id;
        }

        return false;
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('accesscontrol.user.create');
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // No se puede editar a si mismo en ciertos casos
        if ($user->id === $model->id) {
            return false;
        }

        // Los usuarios del sistema no pueden ser editados
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('accesscontrol.user.update');
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // No se puede eliminar a si mismo
        if ($user->id === $model->id) {
            return false;
        }

        // SuperAdmin no puede ser eliminado
        if ($model->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('accesscontrol.user.delete');
    }
}
