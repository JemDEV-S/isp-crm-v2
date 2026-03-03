<?php

declare(strict_types=1);

namespace Modules\AccessControl\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\AccessControl\Events\RoleCreated;
use Modules\AccessControl\Events\RoleDeleted;
use Modules\AccessControl\Events\RoleUpdated;

class LogRoleActivity
{
    public function handleRoleCreated(RoleCreated $event): void
    {
        Log::info('Rol creado', [
            'role_id' => $event->role->id,
            'code' => $event->role->code,
            'created_by' => auth()->id(),
        ]);
    }

    public function handleRoleUpdated(RoleUpdated $event): void
    {
        Log::info('Rol actualizado', [
            'role_id' => $event->role->id,
            'code' => $event->role->code,
            'updated_by' => auth()->id(),
        ]);
    }

    public function handleRoleDeleted(RoleDeleted $event): void
    {
        Log::info('Rol eliminado', [
            'role_id' => $event->role->id,
            'code' => $event->role->code,
            'deleted_by' => auth()->id(),
        ]);
    }

    public function subscribe($events): array
    {
        return [
            RoleCreated::class => 'handleRoleCreated',
            RoleUpdated::class => 'handleRoleUpdated',
            RoleDeleted::class => 'handleRoleDeleted',
        ];
    }
}
