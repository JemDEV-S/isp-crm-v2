<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\ReconnectionCompleted;

class NotifyReconnection
{
    public function handle(ReconnectionCompleted $event): void
    {
        $subscription = $event->subscription;

        // TODO: Implementar notificación por email/SMS al cliente sobre reconexión
        Log::info("Notificación de reconexión: Suscripción #{$subscription->id} - Cliente #{$subscription->customer_id}");
    }
}
