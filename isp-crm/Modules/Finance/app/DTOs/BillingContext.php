<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

use Carbon\Carbon;
use Modules\Subscription\Entities\Subscription;

final readonly class BillingContext
{
    public function __construct(
        public Subscription $subscription,
        public string $billingPeriod,
        public Carbon $periodStart,
        public Carbon $periodEnd,
        public float $basePrice,
        public float $effectivePrice,
        public array $activeAddons,
        public float $addonsTotal,
        public float $discountAmount,
        public ?float $discountPercentage,
        public int $discountMonthsRemaining,
        public float $subtotal,
        public float $taxAmount,
        public float $total,
        public array $commercialSnapshot,
        public string $generationSource,
        public ?int $jobRunId = null,
    ) {}

    public function toCalculationSnapshot(): array
    {
        return [
            'billing_period' => $this->billingPeriod,
            'period_start' => $this->periodStart->toDateString(),
            'period_end' => $this->periodEnd->toDateString(),
            'base_price' => $this->basePrice,
            'effective_price' => $this->effectivePrice,
            'addons' => $this->activeAddons,
            'addons_total' => $this->addonsTotal,
            'discount_amount' => $this->discountAmount,
            'discount_percentage' => $this->discountPercentage,
            'discount_months_remaining' => $this->discountMonthsRemaining,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'total' => $this->total,
            'calculated_at' => now()->toIso8601String(),
        ];
    }
}
