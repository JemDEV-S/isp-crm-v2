<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Subscription\Events\PlanChangeProvisioningFailed;

class NotifyPlanChangeFailed
{
    public function handle(PlanChangeProvisioningFailed $event): void
    {
        $request = $event->planChangeRequest;

        Log::error('Plan change provisioning failed', [
            'plan_change_request_id' => $request->id,
            'subscription_id' => $request->subscription_id,
            'customer_id' => $request->customer_id,
            'reason' => $event->reason,
        ]);

        // TODO: Notificar al equipo técnico y crear incidencia
    }
}
