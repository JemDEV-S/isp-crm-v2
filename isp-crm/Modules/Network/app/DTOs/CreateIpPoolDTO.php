<?php

declare(strict_types=1);

namespace Modules\Network\DTOs;

final readonly class CreateIpPoolDTO
{
    public function __construct(
        public string $name,
        public string $networkCidr,
        public string $gateway,
        public string $type,
        public ?int $deviceId = null,
        public ?string $dnsPrimary = null,
        public ?string $dnsSecondary = null,
        public ?int $vlanId = null,
        public bool $isActive = true,
        public bool $populateAddresses = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            networkCidr: $data['network_cidr'],
            gateway: $data['gateway'],
            type: $data['type'],
            deviceId: isset($data['device_id']) ? (int) $data['device_id'] : null,
            dnsPrimary: $data['dns_primary'] ?? null,
            dnsSecondary: $data['dns_secondary'] ?? null,
            vlanId: isset($data['vlan_id']) ? (int) $data['vlan_id'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
            populateAddresses: (bool) ($data['populate_addresses'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'network_cidr' => $this->networkCidr,
            'gateway' => $this->gateway,
            'type' => $this->type,
            'device_id' => $this->deviceId,
            'dns_primary' => $this->dnsPrimary,
            'dns_secondary' => $this->dnsSecondary,
            'vlan_id' => $this->vlanId,
            'is_active' => $this->isActive,
        ];
    }
}
