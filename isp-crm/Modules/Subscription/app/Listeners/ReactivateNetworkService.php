<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\Network\Services\NetworkProvisioningService;
use Modules\Subscription\Events\SubscriptionReactivated;

class ReactivateNetworkService
{
    public function __construct(
        protected NetworkProvisioningService $provisioningService
    ) {}

    public function handle(SubscriptionReactivated $event): void
    {
        $subscription = $event->subscription;

        // Reactivar el servicio en la red
        $this->provisioningService->reactivateService($subscription);
    }
}
