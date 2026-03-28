<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Modules\Core\Exceptions\BusinessLogicException;
use Modules\Network\DTOs\ProvisionResultDTO;
use Modules\Network\DTOs\ProvisionServiceDTO;
use Modules\Network\Entities\IpAddress;
use Modules\Network\Entities\NapPort;
use Modules\Catalog\Entities\Plan;
use Modules\Subscription\Entities\ServiceInstance;
use Modules\Subscription\Entities\Subscription;

class ProvisioningService
{
    /**
     * This service is the stable application-facing API for provisioning.
     * NetworkProvisioningService remains as the low-level implementation.
     */
    public function __construct(
        protected NetworkProvisioningService $networkProvisioningService,
        protected IpService $ipService,
    ) {}

    public function provisionSubscription(Subscription $subscription): ProvisionResultDTO
    {
        $subscription->loadMissing(['plan.parameters', 'serviceInstance.napPort.napBox']);

        return $this->networkProvisioningService->provisionService(
            $this->buildDtoFromSubscription($subscription)
        );
    }

    public function deprovisionSubscription(Subscription $subscription): bool
    {
        $subscription->loadMissing(['serviceInstance']);

        return $this->networkProvisioningService->deprovisionService(
            subscriptionId: $subscription->id,
            ipAddressId: $subscription->serviceInstance?->ip_address_id,
            napPortId: $subscription->serviceInstance?->nap_port_id,
            pppoeUser: $subscription->serviceInstance?->pppoe_user,
            onuSerial: $subscription->serviceInstance?->onu_serial,
        );
    }

    public function assignIpAddress(int $poolId, int $subscriptionId): IpAddress
    {
        return $this->ipService->assignFreeIp($poolId, $subscriptionId);
    }

    public function createPPPoECredentials(int $subscriptionId, ?string $prefix = null): array
    {
        return [
            'username' => $this->networkProvisioningService->generatePppoeUser($subscriptionId, $prefix),
            'password' => $this->networkProvisioningService->generatePppoePassword(),
        ];
    }

    public function provisionService(ProvisionServiceDTO $dto): ProvisionResultDTO
    {
        return $this->networkProvisioningService->provisionService($dto);
    }

    public function updateServiceInstanceProvisionData(ServiceInstance $serviceInstance, ProvisionResultDTO $result): ServiceInstance
    {
        $payload = $result->toArray();
        $metadata = $serviceInstance->metadata ?? [];
        $metadata['last_provisioning_sync_at'] = now()->toIso8601String();

        $serviceInstance->update([
            'ip_address_id' => $result->ipAddress?->id,
            'nap_port_id' => $result->napPort?->id ?? $serviceInstance->nap_port_id,
            'provisioned_at' => now(),
            'provision_data' => $payload,
            'metadata' => $metadata,
        ]);

        return $serviceInstance->fresh(['ipAddress', 'napPort']);
    }

    /**
     * Valida si el cambio a un nuevo plan es técnicamente factible.
     */
    public function validatePlanFeasibility(Subscription $subscription, Plan $newPlan): array
    {
        $conditions = [];
        $feasible = true;
        $reason = null;

        // Verificar que el pool de IP del nuevo plan existe
        if ($newPlan->ip_pool_id) {
            $pool = \Modules\Network\Entities\IpPool::find($newPlan->ip_pool_id);
            if (! $pool) {
                $feasible = false;
                $reason = 'El pool de IP del plan destino no existe.';
                $conditions[] = 'ip_pool_not_found';
            }
        }

        // Verificar que el plan tiene perfil de red configurado
        if (empty($newPlan->router_profile) && empty($newPlan->olt_profile)) {
            $conditions[] = 'no_network_profile';
        }

        return [
            'feasible' => $feasible,
            'conditions' => $conditions,
            'reason' => $reason,
        ];
    }

    protected function buildDtoFromSubscription(Subscription $subscription): ProvisionServiceDTO
    {
        $serviceInstance = $subscription->serviceInstance;
        $plan = $subscription->plan;

        if (!$serviceInstance || !$plan || !$plan->ip_pool_id) {
            throw new BusinessLogicException('La suscripcion no tiene informacion suficiente para aprovisionamiento');
        }

        $vlanParameter = $plan->parameters->firstWhere('key', 'vlan_id');

        return new ProvisionServiceDTO(
            subscriptionId: $subscription->id,
            planId: $plan->id,
            ipPoolId: $plan->ip_pool_id,
            napBoxId: $serviceInstance->napPort?->nap_box_id,
            pppoeUser: $serviceInstance->pppoe_user,
            pppoePassword: $serviceInstance->pppoe_password,
            onuSerial: $serviceInstance->onu_serial,
            routerProfile: $plan->router_profile,
            oltProfile: $plan->olt_profile,
            vlanId: $vlanParameter ? (int) $vlanParameter->value : null,
        );
    }
}
