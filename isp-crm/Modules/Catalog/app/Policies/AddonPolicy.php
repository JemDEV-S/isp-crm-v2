<?php

declare(strict_types=1);

namespace Modules\Catalog\Policies;

use Modules\AccessControl\Entities\User;
use Modules\Catalog\Entities\Addon;

class AddonPolicy
{
    /**
     * Determine whether the user can view any addons.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'catalog.addon.view',
        ]);
    }

    /**
     * Determine whether the user can view the addon.
     */
    public function view(User $user, Addon $addon): bool
    {
        return $user->hasPermission('catalog.addon.view');
    }

    /**
     * Determine whether the user can create addons.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('catalog.addon.create');
    }

    /**
     * Determine whether the user can update the addon.
     */
    public function update(User $user, Addon $addon): bool
    {
        return $user->hasPermission('catalog.addon.update');
    }

    /**
     * Determine whether the user can delete the addon.
     */
    public function delete(User $user, Addon $addon): bool
    {
        return $user->hasPermission('catalog.addon.delete');
    }

    /**
     * Determine whether the user can restore the addon.
     */
    public function restore(User $user, Addon $addon): bool
    {
        return $user->hasPermission('catalog.addon.delete');
    }

    /**
     * Determine whether the user can permanently delete the addon.
     */
    public function forceDelete(User $user, Addon $addon): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can toggle addon status.
     */
    public function toggleStatus(User $user, Addon $addon): bool
    {
        return $user->hasPermission('catalog.addon.update');
    }
}
