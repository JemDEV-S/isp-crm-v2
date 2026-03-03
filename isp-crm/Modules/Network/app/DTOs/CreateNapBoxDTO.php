<?php

declare(strict_types=1);

namespace Modules\Network\DTOs;

use Carbon\Carbon;

final readonly class CreateNapBoxDTO
{
    public function __construct(
        public int $nodeId,
        public string $code,
        public string $name,
        public string $type,
        public int $totalPorts,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $address = null,
        public string $status = 'active',
        public ?Carbon $installedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nodeId: (int) $data['node_id'],
            code: $data['code'],
            name: $data['name'],
            type: $data['type'],
            totalPorts: (int) $data['total_ports'],
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            address: $data['address'] ?? null,
            status: $data['status'] ?? 'active',
            installedAt: isset($data['installed_at']) ? Carbon::parse($data['installed_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'node_id' => $this->nodeId,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'total_ports' => $this->totalPorts,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'status' => $this->status,
            'installed_at' => $this->installedAt,
        ];
    }
}
