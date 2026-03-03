<?php

declare(strict_types=1);

namespace Modules\Catalog\DTOs;

use Modules\Catalog\Http\Requests\UpdateAddonRequest;

final readonly class UpdateAddonDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?float $price = null,
        public ?bool $isRecurring = null,
        public ?bool $isActive = null,
        public ?array $planIds = null,
    ) {}

    public static function fromRequest(UpdateAddonRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
            price: $request->validated('price') !== null ? (float) $request->validated('price') : null,
            isRecurring: $request->validated('is_recurring') !== null ? (bool) $request->validated('is_recurring') : null,
            isActive: $request->validated('is_active') !== null ? (bool) $request->validated('is_active') : null,
            planIds: $request->validated('plan_ids'),
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->price !== null) $data['price'] = $this->price;
        if ($this->isRecurring !== null) $data['is_recurring'] = $this->isRecurring;
        if ($this->isActive !== null) $data['is_active'] = $this->isActive;

        return $data;
    }
}
