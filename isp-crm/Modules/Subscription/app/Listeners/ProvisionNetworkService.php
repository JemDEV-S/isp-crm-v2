<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\Network\Services\NetworkProvisioningService;
use Modules\Subscription\Events\SubscriptionActivated;

class ProvisionNetworkService
{
    public function __construct(
        protected NetworkProvisioningService $provisioningService
    ) {}

    public function handle(SubscriptionActivated $event): void
    {
        $subscription = $event->subscription;

        // Provisionar el servicio en la red
        $this->provisioningService->provisionService($subscription);
    }
}
