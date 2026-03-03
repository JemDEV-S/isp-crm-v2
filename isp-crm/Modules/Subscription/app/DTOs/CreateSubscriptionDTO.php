<?php

declare(strict_types=1);

namespace Modules\Subscription\DTOs;

use Carbon\Carbon;
use Modules\Catalog\Entities\Plan;
use Modules\Subscription\Enums\BillingCycle;

final readonly class CreateSubscriptionDTO
{
    public function __construct(
        public int $customerId,
        public int $planId,
        public int $addressId,
        public int $billingDay = 1,
        public BillingCycle $billingCycle = BillingCycle::MONTHLY,
        public ?Carbon $startDate = null,
        public ?int $promotionId = null,
        public array $addons = [],
        public ?int $contractedMonths = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customerId: $data['customer_id'],
            planId: $data['plan_id'],
            addressId: $data['address_id'],
            billingDay: $data['billing_day'] ?? 1,
            billingCycle: isset($data['billing_cycle']) ? BillingCycle::from($data['billing_cycle']) : BillingCycle::MONTHLY,
            startDate: isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            promotionId: $data['promotion_id'] ?? null,
            addons: $data['addons'] ?? [],
            contractedMonths: $data['contracted_months'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function getPlan(): Plan
    {
        return Plan::findOrFail($this->planId);
    }
}
