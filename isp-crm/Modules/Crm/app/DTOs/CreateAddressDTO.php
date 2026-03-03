<?php

declare(strict_types=1);

namespace Modules\Crm\DTOs;

use Modules\Crm\Enums\AddressType;

final readonly class CreateAddressDTO
{
    public function __construct(
        public int $customerId,
        public AddressType $type,
        public string $street,
        public string $district,
        public string $city,
        public string $province,
        public ?string $label = null,
        public ?string $number = null,
        public ?string $floor = null,
        public ?string $apartment = null,
        public ?string $reference = null,
        public ?string $postalCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?int $zoneId = null,
        public bool $isDefault = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customerId: $data['customer_id'],
            type: AddressType::from($data['type'] ?? 'service'),
            street: $data['street'],
            district: $data['district'],
            city: $data['city'],
            province: $data['province'],
            label: $data['label'] ?? null,
            number: $data['number'] ?? null,
            floor: $data['floor'] ?? null,
            apartment: $data['apartment'] ?? null,
            reference: $data['reference'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            zoneId: $data['zone_id'] ?? null,
            isDefault: (bool) ($data['is_default'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'type' => $this->type->value,
            'street' => $this->street,
            'district' => $this->district,
            'city' => $this->city,
            'province' => $this->province,
            'label' => $this->label,
            'number' => $this->number,
            'floor' => $this->floor,
            'apartment' => $this->apartment,
            'reference' => $this->reference,
            'postal_code' => $this->postalCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'zone_id' => $this->zoneId,
            'is_default' => $this->isDefault,
        ];
    }
}
