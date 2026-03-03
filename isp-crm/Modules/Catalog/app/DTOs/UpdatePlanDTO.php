<?php

declare(strict_types=1);

namespace Modules\Catalog\DTOs;

use Modules\Catalog\Enums\Technology;
use Modules\Catalog\Http\Requests\UpdatePlanRequest;

final readonly class UpdatePlanDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?Technology $technology = null,
        public ?int $downloadSpeed = null,
        public ?int $uploadSpeed = null,
        public ?float $price = null,
        public ?float $installationFee = null,
        public ?int $ipPoolId = null,
        public ?int $deviceId = null,
        public ?string $routerProfile = null,
        public ?string $oltProfile = null,
        public ?bool $burstEnabled = null,
        public ?int $priority = null,
        public ?bool $isActive = null,
        public ?bool $isVisible = null,
        public ?array $parameters = null,
        public ?array $promotionIds = null,
        public ?array $addonIds = null,
    ) {}

    public static function fromRequest(UpdatePlanRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
            technology: $request->validated('technology') ? Technology::from($request->validated('technology')) : null,
            downloadSpeed: $request->validated('download_speed') !== null ? (int) $request->validated('download_speed') : null,
            uploadSpeed: $request->validated('upload_speed') !== null ? (int) $request->validated('upload_speed') : null,
            price: $request->validated('price') !== null ? (float) $request->validated('price') : null,
            installationFee: $request->validated('installation_fee') !== null ? (float) $request->validated('installation_fee') : null,
            ipPoolId: $request->validated('ip_pool_id'),
            deviceId: $request->validated('device_id'),
            routerProfile: $request->validated('router_profile'),
            oltProfile: $request->validated('olt_profile'),
            burstEnabled: $request->validated('burst_enabled') !== null ? (bool) $request->validated('burst_enabled') : null,
            priority: $request->validated('priority') !== null ? (int) $request->validated('priority') : null,
            isActive: $request->validated('is_active') !== null ? (bool) $request->validated('is_active') : null,
            isVisible: $request->validated('is_visible') !== null ? (bool) $request->validated('is_visible') : null,
            parameters: $request->validated('parameters'),
            promotionIds: $request->validated('promotion_ids'),
            addonIds: $request->validated('addon_ids'),
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->technology !== null) $data['technology'] = $this->technology->value;
        if ($this->downloadSpeed !== null) $data['download_speed'] = $this->downloadSpeed;
        if ($this->uploadSpeed !== null) $data['upload_speed'] = $this->uploadSpeed;
        if ($this->price !== null) $data['price'] = $this->price;
        if ($this->installationFee !== null) $data['installation_fee'] = $this->installationFee;
        if ($this->ipPoolId !== null) $data['ip_pool_id'] = $this->ipPoolId;
        if ($this->deviceId !== null) $data['device_id'] = $this->deviceId;
        if ($this->routerProfile !== null) $data['router_profile'] = $this->routerProfile;
        if ($this->oltProfile !== null) $data['olt_profile'] = $this->oltProfile;
        if ($this->burstEnabled !== null) $data['burst_enabled'] = $this->burstEnabled;
        if ($this->priority !== null) $data['priority'] = $this->priority;
        if ($this->isActive !== null) $data['is_active'] = $this->isActive;
        if ($this->isVisible !== null) $data['is_visible'] = $this->isVisible;

        return $data;
    }
}
