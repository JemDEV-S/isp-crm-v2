<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Subscription\Events\PlanChangeExecuted;

class NotifyPlanChangeResult
{
    public function handle(PlanChangeExecuted $event): void
    {
        $request = $event->planChangeRequest;

        Log::info('Plan change executed successfully', [
            'plan_change_request_id' => $request->id,
            'subscription_id' => $request->subscription_id,
            'customer_id' => $request->customer_id,
            'change_type' => $request->change_type->value,
            'old_plan' => $request->old_plan_snapshot['name'] ?? null,
            'new_plan' => $request->new_plan_snapshot['name'] ?? null,
            'net_difference' => (float) $request->net_difference,
        ]);

        // TODO: Enviar notificación al cliente (email, SMS, push)
    }
}
