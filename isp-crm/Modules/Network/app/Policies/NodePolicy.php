<?php

declare(strict_types=1);

namespace Modules\Network\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AccessControl\Entities\User;
use Modules\Network\Entities\Node;

class NodePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any nodes.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('network.node.view');
    }

    /**
     * Determine whether the user can view the node.
     */
    public function view(User $user, Node $node): bool
    {
        return $user->hasPermission('network.node.view');
    }

    /**
     * Determine whether the user can create nodes.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('network.node.create');
    }

    /**
     * Determine whether the user can update the node.
     */
    public function update(User $user, Node $node): bool
    {
        return $user->hasPermission('network.node.update');
    }

    /**
     * Determine whether the user can delete the node.
     */
    public function delete(User $user, Node $node): bool
    {
        // Can only delete if no devices or NAP boxes
        if ($node->devices()->exists() || $node->napBoxes()->exists()) {
            return false;
        }

        return $user->hasPermission('network.node.delete');
    }
}
