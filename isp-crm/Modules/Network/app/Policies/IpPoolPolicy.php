<?php

declare(strict_types=1);

namespace Modules\Network\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AccessControl\Entities\User;
use Modules\Network\Entities\IpPool;

class IpPoolPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any IP pools.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('network.ippool.view');
    }

    /**
     * Determine whether the user can view the IP pool.
     */
    public function view(User $user, IpPool $ipPool): bool
    {
        return $user->hasPermission('network.ippool.view');
    }

    /**
     * Determine whether the user can create IP pools.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('network.ippool.create');
    }

    /**
     * Determine whether the user can update the IP pool.
     */
    public function update(User $user, IpPool $ipPool): bool
    {
        return $user->hasPermission('network.ippool.update');
    }

    /**
     * Determine whether the user can delete the IP pool.
     */
    public function delete(User $user, IpPool $ipPool): bool
    {
        // Can only delete if no assigned IPs
        if ($ipPool->assignedAddresses()->exists()) {
            return false;
        }

        return $user->hasPermission('network.ippool.delete');
    }

    /**
     * Determine whether the user can assign IPs from this pool.
     */
    public function assignIp(User $user, IpPool $ipPool): bool
    {
        return $user->hasPermission('network.ip.assign');
    }

    /**
     * Determine whether the user can release IPs from this pool.
     */
    public function releaseIp(User $user, IpPool $ipPool): bool
    {
        return $user->hasPermission('network.ip.release');
    }
}
