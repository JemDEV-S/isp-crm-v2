<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\Network\Services\NetworkProvisioningService;
use Modules\Subscription\Enums\ProvisionStatus;
use Modules\Subscription\Events\SubscriptionReactivated;

class ReactivateNetworkService
{
    public function __construct(
        protected NetworkProvisioningService $provisioningService
    ) {}

    public function handle(SubscriptionReactivated $event): void
    {
        $subscription = $event->subscription;
        $serviceInstance = $subscription->serviceInstance()->with('ipAddress')->first();

        if (!$serviceInstance?->ipAddress) {
            return;
        }

        $this->provisioningService->reactivateService(
            $subscription->id,
            $serviceInstance->ipAddress,
            $serviceInstance->pppoe_user
        );

        $serviceInstance->update([
            'provision_status' => ProvisionStatus::ACTIVE,
        ]);
    }
}
