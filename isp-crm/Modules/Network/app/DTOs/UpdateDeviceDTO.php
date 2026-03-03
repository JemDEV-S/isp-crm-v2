<?php

declare(strict_types=1);

namespace Modules\Network\DTOs;

use Modules\Network\Enums\DeviceType;

final readonly class UpdateDeviceDTO
{
    public function __construct(
        public ?int $nodeId = null,
        public ?DeviceType $type = null,
        public ?string $brand = null,
        public ?string $model = null,
        public ?string $serialNumber = null,
        public ?string $ipAddress = null,
        public ?string $macAddress = null,
        public ?string $firmwareVersion = null,
        public ?string $snmpCommunity = null,
        public ?int $apiPort = null,
        public ?string $apiUser = null,
        public ?string $apiPassword = null,
        public ?string $status = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $type = null;
        if (isset($data['type'])) {
            $type = $data['type'] instanceof DeviceType
                ? $data['type']
                : DeviceType::from($data['type']);
        }

        return new self(
            nodeId: isset($data['node_id']) ? (int) $data['node_id'] : null,
            type: $type,
            brand: $data['brand'] ?? null,
            model: $data['model'] ?? null,
            serialNumber: $data['serial_number'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            macAddress: $data['mac_address'] ?? null,
            firmwareVersion: $data['firmware_version'] ?? null,
            snmpCommunity: $data['snmp_community'] ?? null,
            apiPort: isset($data['api_port']) ? (int) $data['api_port'] : null,
            apiUser: $data['api_user'] ?? null,
            apiPassword: $data['api_password'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = array_filter([
            'node_id' => $this->nodeId,
            'type' => $this->type?->value,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serialNumber,
            'ip_address' => $this->ipAddress,
            'mac_address' => $this->macAddress,
            'firmware_version' => $this->firmwareVersion,
            'snmp_community' => $this->snmpCommunity,
            'api_port' => $this->apiPort,
            'api_user' => $this->apiUser,
            'status' => $this->status,
            'notes' => $this->notes,
        ], fn($value) => $value !== null);

        if ($this->apiPassword !== null) {
            $data['api_password_encrypted'] = encrypt($this->apiPassword);
        }

        return $data;
    }
}
