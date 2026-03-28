<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\FieldOps\app\Events\InstallationValidated;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Services\SubscriptionService;

class ActivateSubscriptionFromInstallationValidated
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) {}

    public function handle(InstallationValidated $event): void
    {
        $subscription = $event->workOrder->subscription;

        if (!$subscription || $subscription->status !== SubscriptionStatus::PENDING_INSTALLATION) {
            return;
        }

        $this->subscriptionService->activate($subscription);
    }
}
