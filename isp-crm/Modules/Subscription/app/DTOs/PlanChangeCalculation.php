<?php

declare(strict_types=1);

namespace Modules\Subscription\DTOs;

final readonly class PlanChangeCalculation
{
    public function __construct(
        public string $changeType,
        public float $oldMonthlyPrice,
        public float $newMonthlyPrice,
        public int $daysRemainingInCycle,
        public int $totalDaysInCycle,
        public float $prorateCredit,
        public float $prorateDebit,
        public float $netDifference,
        public string $billingAdjustmentType,
        public array $oldPlanSnapshot,
        public array $newPlanSnapshot,
    ) {}

    public function toArray(): array
    {
        return [
            'change_type' => $this->changeType,
            'old_monthly_price' => $this->oldMonthlyPrice,
            'new_monthly_price' => $this->newMonthlyPrice,
            'days_remaining_in_cycle' => $this->daysRemainingInCycle,
            'total_days_in_cycle' => $this->totalDaysInCycle,
            'prorate_credit' => $this->prorateCredit,
            'prorate_debit' => $this->prorateDebit,
            'net_difference' => $this->netDifference,
            'billing_adjustment_type' => $this->billingAdjustmentType,
            'old_plan_snapshot' => $this->oldPlanSnapshot,
            'new_plan_snapshot' => $this->newPlanSnapshot,
        ];
    }
}
