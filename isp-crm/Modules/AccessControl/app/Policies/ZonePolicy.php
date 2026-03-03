<?php

declare(strict_types=1);

namespace Modules\AccessControl\Policies;

use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Entities\Zone;

class ZonePolicy
{
    /**
     * Determine if the user can view any zones.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('accesscontrol.zone.view');
    }

    /**
     * Determine if the user can view the zone.
     */
    public function view(User $user, Zone $zone): bool
    {
        return $user->hasPermission('accesscontrol.zone.view');
    }

    /**
     * Determine if the user can create zones.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('accesscontrol.zone.create');
    }

    /**
     * Determine if the user can update the zone.
     */
    public function update(User $user, Zone $zone): bool
    {
        return $user->hasPermission('accesscontrol.zone.update');
    }

    /**
     * Determine if the user can delete the zone.
     */
    public function delete(User $user, Zone $zone): bool
    {
        // No se puede eliminar si tiene zonas hijas
        if ($zone->hasChildren()) {
            return false;
        }

        // No se puede eliminar si tiene usuarios asignados
        if ($zone->users()->exists()) {
            return false;
        }

        return $user->hasPermission('accesscontrol.zone.delete');
    }
}
