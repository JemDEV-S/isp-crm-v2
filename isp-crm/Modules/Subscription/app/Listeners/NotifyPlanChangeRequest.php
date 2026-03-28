<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Subscription\Events\PlanChangeRequested;

class NotifyPlanChangeRequest
{
    public function handle(PlanChangeRequested $event): void
    {
        $request = $event->planChangeRequest;

        if (! $request->requires_approval) {
            return;
        }

        Log::info('Plan change request requires approval', [
            'plan_change_request_id' => $request->id,
            'subscription_id' => $request->subscription_id,
            'customer_id' => $request->customer_id,
            'change_type' => $request->change_type->value,
            'old_plan' => $request->old_plan_snapshot['name'] ?? null,
            'new_plan' => $request->new_plan_snapshot['name'] ?? null,
        ]);

        // TODO: Integrar con sistema de notificaciones cuando esté disponible
    }
}
