<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Subscription\Events\SubscriptionPlanChanged;

class LogPlanChange
{
    public function handle(SubscriptionPlanChanged $event): void
    {
        Log::channel('daily')->info('Subscription plan changed', [
            'subscription_id' => $event->subscription->id,
            'customer_id' => $event->subscription->customer_id,
            'old_plan' => $event->oldPlanSnapshot['name'] ?? null,
            'new_plan' => $event->newPlanSnapshot['name'] ?? null,
            'old_price' => $event->oldPlanSnapshot['price'] ?? null,
            'new_price' => $event->newPlanSnapshot['price'] ?? null,
            'changed_at' => now()->toIso8601String(),
        ]);
    }
}
