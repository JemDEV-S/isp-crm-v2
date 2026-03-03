<?php

declare(strict_types=1);

namespace Modules\Catalog\DTOs;

use Carbon\Carbon;
use Modules\Catalog\Enums\AppliesTo;
use Modules\Catalog\Enums\DiscountType;
use Modules\Catalog\Http\Requests\UpdatePromotionRequest;

final readonly class UpdatePromotionDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?DiscountType $discountType = null,
        public ?float $discountValue = null,
        public ?AppliesTo $appliesTo = null,
        public ?int $minMonths = null,
        public ?int $discountMonths = null,
        public ?Carbon $validFrom = null,
        public ?Carbon $validUntil = null,
        public ?int $maxUses = null,
        public ?bool $isActive = null,
        public ?array $planIds = null,
    ) {}

    public static function fromRequest(UpdatePromotionRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
            discountType: $request->validated('discount_type') ? DiscountType::from($request->validated('discount_type')) : null,
            discountValue: $request->validated('discount_value') !== null ? (float) $request->validated('discount_value') : null,
            appliesTo: $request->validated('applies_to') ? AppliesTo::from($request->validated('applies_to')) : null,
            minMonths: $request->validated('min_months') !== null ? (int) $request->validated('min_months') : null,
            discountMonths: $request->validated('discount_months'),
            validFrom: $request->validated('valid_from') ? Carbon::parse($request->validated('valid_from')) : null,
            validUntil: $request->validated('valid_until') ? Carbon::parse($request->validated('valid_until')) : null,
            maxUses: $request->validated('max_uses'),
            isActive: $request->validated('is_active') !== null ? (bool) $request->validated('is_active') : null,
            planIds: $request->validated('plan_ids'),
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->discountType !== null) $data['discount_type'] = $this->discountType->value;
        if ($this->discountValue !== null) $data['discount_value'] = $this->discountValue;
        if ($this->appliesTo !== null) $data['applies_to'] = $this->appliesTo->value;
        if ($this->minMonths !== null) $data['min_months'] = $this->minMonths;
        if ($this->discountMonths !== null) $data['discount_months'] = $this->discountMonths;
        if ($this->validFrom !== null) $data['valid_from'] = $this->validFrom;
        if ($this->validUntil !== null) $data['valid_until'] = $this->validUntil;
        if ($this->maxUses !== null) $data['max_uses'] = $this->maxUses;
        if ($this->isActive !== null) $data['is_active'] = $this->isActive;

        return $data;
    }
}
