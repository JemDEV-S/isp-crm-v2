<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\Subscription\Events\SubscriptionPlanChanged;
use Modules\Subscription\Services\SubscriptionContractService;

class UpdateCommercialSnapshot
{
    public function __construct(
        protected SubscriptionContractService $contractService
    ) {}

    public function handle(SubscriptionPlanChanged $event): void
    {
        $subscription = $event->subscription->fresh(['plan', 'addons', 'promotion']);

        $snapshot = $this->contractService->freezeCommercialSnapshot($subscription);

        $subscription->update([
            'commercial_snapshot' => $snapshot,
        ]);
    }
}
