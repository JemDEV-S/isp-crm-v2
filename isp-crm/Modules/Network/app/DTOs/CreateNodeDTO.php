<?php

declare(strict_types=1);

namespace Modules\Network\DTOs;

use Carbon\Carbon;

final readonly class CreateNodeDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public string $type,
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $altitude = null,
        public string $status = 'active',
        public ?string $description = null,
        public ?Carbon $commissionedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            type: $data['type'],
            address: $data['address'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            altitude: isset($data['altitude']) ? (float) $data['altitude'] : null,
            status: $data['status'] ?? 'active',
            description: $data['description'] ?? null,
            commissionedAt: isset($data['commissioned_at']) ? Carbon::parse($data['commissioned_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'altitude' => $this->altitude,
            'status' => $this->status,
            'description' => $this->description,
            'commissioned_at' => $this->commissionedAt,
        ];
    }
}
