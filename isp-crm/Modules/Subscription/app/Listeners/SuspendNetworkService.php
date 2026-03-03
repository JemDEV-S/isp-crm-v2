<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\Network\Services\NetworkProvisioningService;
use Modules\Subscription\Events\SubscriptionSuspended;

class SuspendNetworkService
{
    public function __construct(
        protected NetworkProvisioningService $provisioningService
    ) {}

    public function handle(SubscriptionSuspended $event): void
    {
        $subscription = $event->subscription;

        // Suspender el servicio en la red
        $this->provisioningService->suspendService($subscription);
    }
}
