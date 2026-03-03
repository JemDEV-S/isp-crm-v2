<?php

declare(strict_types=1);

namespace Modules\Catalog\DTOs;

use Modules\Catalog\Http\Requests\StoreAddonRequest;

final readonly class CreateAddonDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description,
        public float $price,
        public bool $isRecurring,
        public bool $isActive,
        public array $planIds = [],
    ) {}

    public static function fromRequest(StoreAddonRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            price: (float) $request->validated('price'),
            isRecurring: (bool) $request->validated('is_recurring', true),
            isActive: (bool) $request->validated('is_active', true),
            planIds: $request->validated('plan_ids', []),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'is_recurring' => $this->isRecurring,
            'is_active' => $this->isActive,
        ];
    }
}
