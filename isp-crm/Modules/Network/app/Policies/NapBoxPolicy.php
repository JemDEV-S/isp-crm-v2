<?php

declare(strict_types=1);

namespace Modules\Network\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AccessControl\Entities\User;
use Modules\Network\Entities\NapBox;

class NapBoxPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any NAP boxes.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('network.napbox.view');
    }

    /**
     * Determine whether the user can view the NAP box.
     */
    public function view(User $user, NapBox $napBox): bool
    {
        return $user->hasPermission('network.napbox.view');
    }

    /**
     * Determine whether the user can create NAP boxes.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('network.napbox.create');
    }

    /**
     * Determine whether the user can update the NAP box.
     */
    public function update(User $user, NapBox $napBox): bool
    {
        return $user->hasPermission('network.napbox.update');
    }

    /**
     * Determine whether the user can delete the NAP box.
     */
    public function delete(User $user, NapBox $napBox): bool
    {
        // Can only delete if no occupied ports
        if ($napBox->occupiedPorts()->exists()) {
            return false;
        }

        return $user->hasPermission('network.napbox.delete');
    }

    /**
     * Determine whether the user can assign ports.
     */
    public function assignPort(User $user, NapBox $napBox): bool
    {
        return $user->hasPermission('network.napbox.assign');
    }

    /**
     * Determine whether the user can release ports.
     */
    public function releasePort(User $user, NapBox $napBox): bool
    {
        return $user->hasPermission('network.napbox.release');
    }
}
