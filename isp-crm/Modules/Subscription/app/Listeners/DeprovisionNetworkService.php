<?php

declare(strict_types=1);

namespace Modules\Subscription\Listeners;

use Modules\Network\Services\NetworkProvisioningService;
use Modules\Subscription\Enums\ProvisionStatus;
use Modules\Subscription\Events\SubscriptionCancelled;

class DeprovisionNetworkService
{
    public function __construct(
        protected NetworkProvisioningService $provisioningService
    ) {}

    public function handle(SubscriptionCancelled $event): void
    {
        $subscription = $event->subscription;
        $serviceInstance = $subscription->serviceInstance;

        $this->provisioningService->deprovisionService(
            subscriptionId: $subscription->id,
            ipAddressId: $serviceInstance?->ip_address_id,
            napPortId: $serviceInstance?->nap_port_id,
            pppoeUser: $serviceInstance?->pppoe_user,
            onuSerial: $serviceInstance?->onu_serial,
        );

        if ($serviceInstance) {
            $serviceInstance->update([
                'provision_status' => ProvisionStatus::DEPROVISIONED,
                'ip_address_id' => null,
                'nap_port_id' => null,
            ]);
        }
    }
}
