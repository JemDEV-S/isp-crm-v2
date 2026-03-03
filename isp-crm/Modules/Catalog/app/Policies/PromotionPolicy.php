<?php

declare(strict_types=1);

namespace Modules\Catalog\Policies;

use Modules\AccessControl\Entities\User;
use Modules\Catalog\Entities\Promotion;

class PromotionPolicy
{
    /**
     * Determine whether the user can view any promotions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'catalog.promotion.view',
        ]);
    }

    /**
     * Determine whether the user can view the promotion.
     */
    public function view(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('catalog.promotion.view');
    }

    /**
     * Determine whether the user can create promotions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('catalog.promotion.create');
    }

    /**
     * Determine whether the user can update the promotion.
     */
    public function update(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('catalog.promotion.update');
    }

    /**
     * Determine whether the user can delete the promotion.
     */
    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('catalog.promotion.delete');
    }

    /**
     * Determine whether the user can restore the promotion.
     */
    public function restore(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('catalog.promotion.delete');
    }

    /**
     * Determine whether the user can permanently delete the promotion.
     */
    public function forceDelete(User $user, Promotion $promotion): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can toggle promotion status.
     */
    public function toggleStatus(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('catalog.promotion.update');
    }

    /**
     * Determine whether the user can apply the promotion.
     */
    public function apply(User $user, Promotion $promotion): bool
    {
        // Vendedores pueden aplicar promociones válidas
        return $user->hasAnyPermission([
            'catalog.promotion.apply',
            'subscription.contract.create',
        ]) && $promotion->isValid();
    }
}
