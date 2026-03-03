<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\Network\Services\NetworkProvisioningService;
use Modules\Subscription\Events\SubscriptionCancelled;

class DeprovisionNetworkService
{
    public function __construct(
        protected NetworkProvisioningService $provisioningService
    ) {}

    public function handle(SubscriptionCancelled $event): void
    {
        $subscription = $event->subscription;

        // Desaprovisionar el servicio de la red
        $this->provisioningService->deprovisionService($subscription);
    }
}
