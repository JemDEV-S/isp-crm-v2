<?php

declare(strict_types=1);

namespace Modules\Catalog\DTOs;

use Modules\Catalog\Enums\Technology;
use Modules\Catalog\Http\Requests\StorePlanRequest;

final readonly class CreatePlanDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description,
        public Technology $technology,
        public int $downloadSpeed,
        public int $uploadSpeed,
        public float $price,
        public float $installationFee,
        public ?int $ipPoolId,
        public ?int $deviceId,
        public ?string $routerProfile,
        public ?string $oltProfile,
        public bool $burstEnabled,
        public int $priority,
        public bool $isActive,
        public bool $isVisible,
        public array $parameters = [],
        public array $promotionIds = [],
        public array $addonIds = [],
    ) {}

    public static function fromRequest(StorePlanRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            technology: Technology::from($request->validated('technology')),
            downloadSpeed: (int) $request->validated('download_speed'),
            uploadSpeed: (int) $request->validated('upload_speed'),
            price: (float) $request->validated('price'),
            installationFee: (float) $request->validated('installation_fee', 0),
            ipPoolId: $request->validated('ip_pool_id'),
            deviceId: $request->validated('device_id'),
            routerProfile: $request->validated('router_profile'),
            oltProfile: $request->validated('olt_profile'),
            burstEnabled: (bool) $request->validated('burst_enabled', false),
            priority: (int) $request->validated('priority', 4),
            isActive: (bool) $request->validated('is_active', true),
            isVisible: (bool) $request->validated('is_visible', true),
            parameters: $request->validated('parameters', []),
            promotionIds: $request->validated('promotion_ids', []),
            addonIds: $request->validated('addon_ids', []),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'technology' => $this->technology->value,
            'download_speed' => $this->downloadSpeed,
            'upload_speed' => $this->uploadSpeed,
            'price' => $this->price,
            'installation_fee' => $this->installationFee,
            'ip_pool_id' => $this->ipPoolId,
            'device_id' => $this->deviceId,
            'router_profile' => $this->routerProfile,
            'olt_profile' => $this->oltProfile,
            'burst_enabled' => $this->burstEnabled,
            'priority' => $this->priority,
            'is_active' => $this->isActive,
            'is_visible' => $this->isVisible,
        ];
    }
}
