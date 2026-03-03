<?php

declare(strict_types=1);

namespace Modules\Network\DTOs;

final readonly class ProvisionServiceDTO
{
    public function __construct(
        public int $subscriptionId,
        public int $planId,
        public int $ipPoolId,
        public ?int $napBoxId = null,
        public ?string $pppoeUser = null,
        public ?string $pppoePassword = null,
        public ?string $onuSerial = null,
        public ?string $routerProfile = null,
        public ?string $oltProfile = null,
        public ?int $vlanId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionId: (int) $data['subscription_id'],
            planId: (int) $data['plan_id'],
            ipPoolId: (int) $data['ip_pool_id'],
            napBoxId: isset($data['nap_box_id']) ? (int) $data['nap_box_id'] : null,
            pppoeUser: $data['pppoe_user'] ?? null,
            pppoePassword: $data['pppoe_password'] ?? null,
            onuSerial: $data['onu_serial'] ?? null,
            routerProfile: $data['router_profile'] ?? null,
            oltProfile: $data['olt_profile'] ?? null,
            vlanId: isset($data['vlan_id']) ? (int) $data['vlan_id'] : null,
        );
    }
}
