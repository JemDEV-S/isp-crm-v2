<?php

declare(strict_types=1);

namespace Modules\AccessControl\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\AccessControl\Events\UserCreated;
use Modules\AccessControl\Events\UserDeleted;
use Modules\AccessControl\Events\UserUpdated;

class LogUserActivity
{
    public function handleUserCreated(UserCreated $event): void
    {
        Log::info('Usuario creado', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'created_by' => auth()->id(),
        ]);
    }

    public function handleUserUpdated(UserUpdated $event): void
    {
        Log::info('Usuario actualizado', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'updated_by' => auth()->id(),
        ]);
    }

    public function handleUserDeleted(UserDeleted $event): void
    {
        Log::info('Usuario eliminado', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'deleted_by' => auth()->id(),
        ]);
    }

    public function subscribe($events): array
    {
        return [
            UserCreated::class => 'handleUserCreated',
            UserUpdated::class => 'handleUserUpdated',
            UserDeleted::class => 'handleUserDeleted',
        ];
    }
}
