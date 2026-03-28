<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Modules\Finance\DTOs\BillingContext;
use Modules\Subscription\Entities\Subscription;

class BillingCalculator
{
    public function buildContext(
        Subscription $subscription,
        string $billingPeriod,
        string $generationSource = 'scheduled',
        ?int $jobRunId = null,
    ): BillingContext {
        $subscription->loadMissing(['customer', 'addons', 'plan']);

        [$periodStart, $periodEnd] = $this->calculatePeriodDates($subscription, $billingPeriod);
        [$discountAmount, $discountPercentage, $discountMonthsRemaining] = $this->calculateDiscount($subscription);

        $basePrice = (float) $subscription->monthly_price;
        $effectivePrice = round($basePrice - $discountAmount, 2);
        $activeAddons = $this->resolveActiveAddons($subscription, $periodStart);
        $addonsTotal = round(array_sum(array_column($activeAddons, 'price')), 2);

        $subtotal = round($effectivePrice + $addonsTotal, 2);
        $taxAmount = $this->calculateTax($subtotal, $subscription);
        $total = round($subtotal + $taxAmount, 2);

        return new BillingContext(
            subscription: $subscription,
            billingPeriod: $billingPeriod,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            basePrice: $basePrice,
            effectivePrice: $effectivePrice,
            activeAddons: $activeAddons,
            addonsTotal: $addonsTotal,
            discountAmount: $discountAmount,
            discountPercentage: $discountPercentage,
            discountMonthsRemaining: $discountMonthsRemaining,
            subtotal: $subtotal,
            taxAmount: $taxAmount,
            total: $total,
            commercialSnapshot: $subscription->commercial_snapshot ?? [],
            generationSource: $generationSource,
            jobRunId: $jobRunId,
        );
    }

    protected function calculatePeriodDates(Subscription $subscription, string $billingPeriod): array
    {
        $billingDay = max(1, min(28, (int) $subscription->billing_day));
        $periodDate = Carbon::parse($billingPeriod . '-01');

        $daysInMonth = $periodDate->daysInMonth;
        $effectiveDay = min($billingDay, $daysInMonth);

        $periodStart = $periodDate->copy()->day($effectiveDay);

        $nextMonth = $periodDate->copy()->addMonth();
        $daysInNextMonth = $nextMonth->daysInMonth;
        $effectiveEndDay = min($billingDay, $daysInNextMonth);
        $periodEnd = $nextMonth->copy()->day($effectiveEndDay)->subDay();

        return [$periodStart, $periodEnd];
    }

    protected function calculateDiscount(Subscription $subscription): array
    {
        $discountAmount = 0.0;
        $discountPercentage = (float) ($subscription->discount_percentage ?? 0);
        $discountMonthsRemaining = (int) ($subscription->discount_months_remaining ?? 0);

        if ($discountMonthsRemaining > 0 && $discountPercentage > 0) {
            $discountAmount = round((float) $subscription->monthly_price * ($discountPercentage / 100), 2);
        }

        return [$discountAmount, $discountPercentage, $discountMonthsRemaining];
    }

    protected function calculateTax(float $subtotal, Subscription $subscription): float
    {
        $taxEnabled = config('finance.billing.tax_enabled', false);

        if (!$taxEnabled) {
            return 0.0;
        }

        $taxRate = (float) config('finance.billing.tax_rate', 0.00);

        return round($subtotal * $taxRate, 2);
    }

    protected function resolveActiveAddons(Subscription $subscription, Carbon $periodStart): array
    {
        return $subscription->addons
            ->filter(function ($addon) use ($periodStart) {
                $startDate = $addon->pivot->start_date
                    ? Carbon::parse($addon->pivot->start_date)
                    : null;
                $endDate = $addon->pivot->end_date
                    ? Carbon::parse($addon->pivot->end_date)
                    : null;

                if ($startDate && $startDate->greaterThan($periodStart)) {
                    return false;
                }

                if ($endDate && $endDate->lessThan($periodStart)) {
                    return false;
                }

                return true;
            })
            ->map(fn($addon) => [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => (float) $addon->pivot->price,
            ])
            ->values()
            ->toArray();
    }
}
