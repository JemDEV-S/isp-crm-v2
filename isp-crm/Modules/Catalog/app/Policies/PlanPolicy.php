<?php

declare(strict_types=1);

namespace Modules\Catalog\Policies;

use Modules\AccessControl\Entities\User;
use Modules\Catalog\Entities\Plan;

class PlanPolicy
{
    /**
     * Determine whether the user can view any plans.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'catalog.plan.view',
        ]);
    }

    /**
     * Determine whether the user can view the plan.
     */
    public function view(User $user, Plan $plan): bool
    {
        return $user->hasPermission('catalog.plan.view');
    }

    /**
     * Determine whether the user can create plans.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('catalog.plan.create');
    }

    /**
     * Determine whether the user can update the plan.
     */
    public function update(User $user, Plan $plan): bool
    {
        return $user->hasPermission('catalog.plan.update');
    }

    /**
     * Determine whether the user can delete the plan.
     */
    public function delete(User $user, Plan $plan): bool
    {
        return $user->hasPermission('catalog.plan.delete');
    }

    /**
     * Determine whether the user can restore the plan.
     */
    public function restore(User $user, Plan $plan): bool
    {
        return $user->hasPermission('catalog.plan.delete');
    }

    /**
     * Determine whether the user can permanently delete the plan.
     */
    public function forceDelete(User $user, Plan $plan): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can toggle plan status.
     */
    public function toggleStatus(User $user, Plan $plan): bool
    {
        return $user->hasPermission('catalog.plan.update');
    }

    /**
     * Determine whether the user can manage plan promotions.
     */
    public function managePromotions(User $user, Plan $plan): bool
    {
        return $user->hasPermission('catalog.plan.update');
    }

    /**
     * Determine whether the user can manage plan addons.
     */
    public function manageAddons(User $user, Plan $plan): bool
    {
        return $user->hasPermission('catalog.plan.update');
    }
}
