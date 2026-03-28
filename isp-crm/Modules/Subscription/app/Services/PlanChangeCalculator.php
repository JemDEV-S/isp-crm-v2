<?php

declare(strict_types=1);

namespace Modules\Subscription\Services;

use Carbon\Carbon;
use Modules\Catalog\Entities\Plan;
use Modules\Subscription\DTOs\PlanChangeCalculation;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Enums\BillingAdjustmentType;
use Modules\Subscription\Enums\PlanChangeType;

class PlanChangeCalculator
{
    public function calculate(
        Subscription $subscription,
        Plan $newPlan,
        string $effectiveMode = 'immediate',
    ): PlanChangeCalculation {
        $oldPrice = (float) $subscription->monthly_price;
        $newPrice = (float) $newPlan->price;
        $changeType = PlanChangeType::determine($oldPrice, $newPrice);

        $oldPlanSnapshot = $this->buildPlanSnapshot($subscription->plan, $subscription);
        $newPlanSnapshot = $this->buildPlanSnapshot($newPlan, $subscription);

        if ($effectiveMode !== 'immediate') {
            return new PlanChangeCalculation(
                changeType: $changeType->value,
                oldMonthlyPrice: $oldPrice,
                newMonthlyPrice: $newPrice,
                daysRemainingInCycle: $this->getDaysRemainingInCycle($subscription),
                totalDaysInCycle: $this->getTotalDaysInCycle($subscription),
                prorateCredit: 0,
                prorateDebit: 0,
                netDifference: 0,
                billingAdjustmentType: BillingAdjustmentType::NONE->value,
                oldPlanSnapshot: $oldPlanSnapshot,
                newPlanSnapshot: $newPlanSnapshot,
            );
        }

        $daysRemaining = $this->getDaysRemainingInCycle($subscription);
        $totalDays = $this->getTotalDaysInCycle($subscription);

        $dailyOldRate = $totalDays > 0 ? $oldPrice / $totalDays : 0;
        $dailyNewRate = $totalDays > 0 ? $newPrice / $totalDays : 0;

        $prorateCredit = round($dailyOldRate * $daysRemaining, 2);
        $prorateDebit = round($dailyNewRate * $daysRemaining, 2);
        $netDifference = round($prorateDebit - $prorateCredit, 2);

        $billingAdjustment = $this->determineBillingAdjustment(
            $changeType->value,
            $effectiveMode,
            $netDifference,
        );

        return new PlanChangeCalculation(
            changeType: $changeType->value,
            oldMonthlyPrice: $oldPrice,
            newMonthlyPrice: $newPrice,
            daysRemainingInCycle: $daysRemaining,
            totalDaysInCycle: $totalDays,
            prorateCredit: $prorateCredit,
            prorateDebit: $prorateDebit,
            netDifference: $netDifference,
            billingAdjustmentType: $billingAdjustment,
            oldPlanSnapshot: $oldPlanSnapshot,
            newPlanSnapshot: $newPlanSnapshot,
        );
    }

    protected function getDaysRemainingInCycle(Subscription $subscription): int
    {
        $today = Carbon::today();
        $billingDay = $subscription->billing_day;

        $currentCycleStart = Carbon::create($today->year, $today->month, $billingDay);
        if ($currentCycleStart->isAfter($today)) {
            $currentCycleStart->subMonth();
        }

        $currentCycleEnd = (clone $currentCycleStart)->addMonth();

        return (int) $today->diffInDays($currentCycleEnd);
    }

    protected function getTotalDaysInCycle(Subscription $subscription): int
    {
        $today = Carbon::today();
        $billingDay = $subscription->billing_day;

        $currentCycleStart = Carbon::create($today->year, $today->month, $billingDay);
        if ($currentCycleStart->isAfter($today)) {
            $currentCycleStart->subMonth();
        }

        $currentCycleEnd = (clone $currentCycleStart)->addMonth();

        return (int) $currentCycleStart->diffInDays($currentCycleEnd);
    }

    protected function buildPlanSnapshot(Plan $plan, Subscription $subscription): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'code' => $plan->code,
            'price' => (float) $plan->price,
            'download_speed' => $plan->download_speed,
            'upload_speed' => $plan->upload_speed,
            'technology' => $plan->technology?->value,
            'ip_pool_id' => $plan->ip_pool_id,
            'router_profile' => $plan->router_profile,
            'olt_profile' => $plan->olt_profile,
            'parameters' => $plan->parameters?->pluck('value', 'key')->toArray() ?? [],
            'snapshot_at' => now()->toIso8601String(),
        ];
    }

    protected function determineBillingAdjustment(
        string $changeType,
        string $effectiveMode,
        float $netDifference,
    ): string {
        if ($effectiveMode !== 'immediate') {
            return BillingAdjustmentType::NONE->value;
        }

        if ($netDifference > 0) {
            return BillingAdjustmentType::INVOICE->value;
        }

        if ($netDifference < 0) {
            $creditTo = config('subscription.plan_change.downgrade_credit_to', 'wallet');
            return $creditTo === 'wallet'
                ? BillingAdjustmentType::WALLET_CREDIT->value
                : BillingAdjustmentType::CREDIT_NOTE->value;
        }

        return BillingAdjustmentType::NONE->value;
    }
}
