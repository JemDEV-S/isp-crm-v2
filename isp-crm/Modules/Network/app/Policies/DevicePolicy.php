<?php

declare(strict_types=1);

namespace Modules\Network\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AccessControl\Entities\User;
use Modules\Network\Entities\Device;

class DevicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any devices.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('network.device.view');
    }

    /**
     * Determine whether the user can view the device.
     */
    public function view(User $user, Device $device): bool
    {
        return $user->hasPermission('network.device.view');
    }

    /**
     * Determine whether the user can create devices.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('network.device.create');
    }

    /**
     * Determine whether the user can update the device.
     */
    public function update(User $user, Device $device): bool
    {
        return $user->hasPermission('network.device.update');
    }

    /**
     * Determine whether the user can delete the device.
     */
    public function delete(User $user, Device $device): bool
    {
        return $user->hasPermission('network.device.delete');
    }

    /**
     * Determine whether the user can configure the device.
     */
    public function configure(User $user, Device $device): bool
    {
        return $user->hasPermission('network.device.configure');
    }

    /**
     * Determine whether the user can test device connection.
     */
    public function testConnection(User $user, Device $device): bool
    {
        return $user->hasPermission('network.device.configure');
    }

    /**
     * Determine whether the user can view device system info.
     */
    public function viewSystemInfo(User $user, Device $device): bool
    {
        return $user->hasPermission('network.device.view');
    }
}
