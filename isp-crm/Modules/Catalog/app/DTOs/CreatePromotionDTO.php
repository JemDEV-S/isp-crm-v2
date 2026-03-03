<?php

declare(strict_types=1);

namespace Modules\Catalog\DTOs;

use Carbon\Carbon;
use Modules\Catalog\Enums\AppliesTo;
use Modules\Catalog\Enums\DiscountType;
use Modules\Catalog\Http\Requests\StorePromotionRequest;

final readonly class CreatePromotionDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description,
        public DiscountType $discountType,
        public float $discountValue,
        public AppliesTo $appliesTo,
        public int $minMonths,
        public ?int $discountMonths,
        public ?Carbon $validFrom,
        public ?Carbon $validUntil,
        public ?int $maxUses,
        public bool $isActive,
        public array $planIds = [],
    ) {}

    public static function fromRequest(StorePromotionRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            discountType: DiscountType::from($request->validated('discount_type')),
            discountValue: (float) $request->validated('discount_value'),
            appliesTo: AppliesTo::from($request->validated('applies_to')),
            minMonths: (int) $request->validated('min_months', 0),
            discountMonths: $request->validated('discount_months'),
            validFrom: $request->validated('valid_from') ? Carbon::parse($request->validated('valid_from')) : null,
            validUntil: $request->validated('valid_until') ? Carbon::parse($request->validated('valid_until')) : null,
            maxUses: $request->validated('max_uses'),
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
            'discount_type' => $this->discountType->value,
            'discount_value' => $this->discountValue,
            'applies_to' => $this->appliesTo->value,
            'min_months' => $this->minMonths,
            'discount_months' => $this->discountMonths,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'max_uses' => $this->maxUses,
            'is_active' => $this->isActive,
        ];
    }
}
