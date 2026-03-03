<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Policies;

use Modules\AccessControl\app\Models\User;
use Modules\FieldOps\app\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'fieldops.workorder.view',
            'fieldops.workorder.view.own',
            'fieldops.workorder.view.zone',
        ]);
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        if ($user->hasPermission('fieldops.workorder.view')) {
            return true;
        }

        if ($user->hasPermission('fieldops.workorder.view.zone')) {
            return $user->zone_id === $workOrder->address->zone_id;
        }

        if ($user->hasPermission('fieldops.workorder.view.own')) {
            return $user->id === $workOrder->assigned_to || $user->id === $workOrder->created_by;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fieldops.workorder.create');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasPermission('fieldops.workorder.update');
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasPermission('fieldops.workorder.delete');
    }

    public function assign(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasPermission('fieldops.workorder.assign');
    }

    public function start(User $user, WorkOrder $workOrder): bool
    {
        // Solo el técnico asignado puede iniciar
        return $user->hasPermission('fieldops.workorder.start')
            && $user->id === $workOrder->assigned_to;
    }

    public function complete(User $user, WorkOrder $workOrder): bool
    {
        // Solo el técnico asignado puede completar
        return $user->hasPermission('fieldops.workorder.complete')
            && $user->id === $workOrder->assigned_to
            && !is_null($workOrder->started_at);
    }

    public function cancel(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasPermission('fieldops.workorder.cancel');
    }

    public function reassign(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasPermission('fieldops.workorder.reassign');
    }
}
