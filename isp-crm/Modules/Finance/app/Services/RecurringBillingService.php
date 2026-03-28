<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\LazyCollection;
use Modules\Finance\DTOs\BillingContext;
use Modules\Finance\Entities\BillingIncident;
use Modules\Finance\Entities\BillingJobRun;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\BillingIncidentType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;
use Modules\Finance\Events\InvoiceBatchCompleted;
use Modules\Finance\Events\InvoiceGenerationFailed;
use Modules\Finance\Events\InvoiceGenerationSkipped;
use Modules\Finance\Events\InvoiceGenerationStarted;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Enums\SubscriptionStatus;

class RecurringBillingService
{
    public function __construct(
        protected BillingCalculator $calculator,
        protected InvoiceService $invoiceService,
    ) {}

    public function runBillingCycle(
        string $billingPeriod,
        string $triggeredBy = 'scheduler',
        ?int $userId = null,
    ): BillingJobRun {
        $jobRun = BillingJobRun::create([
            'billing_period' => $billingPeriod,
            'status' => 'running',
            'triggered_by' => $triggeredBy,
            'user_id' => $userId,
        ]);

        event(new InvoiceGenerationStarted($jobRun));

        try {
            $subscriptions = $this->getEligibleSubscriptions($billingPeriod);

            $subscriptions->each(function (Subscription $subscription) use ($billingPeriod, $jobRun) {
                $jobRun->incrementProcessed();

                try {
                    if (!$this->isEligible($subscription, $billingPeriod)) {
                        $incident = $this->createIncident(
                            $jobRun,
                            $subscription,
                            BillingIncidentType::SKIPPED,
                            'La suscripcion no cumple con los criterios de elegibilidad',
                        );
                        $jobRun->incrementSkipped();
                        event(new InvoiceGenerationSkipped($subscription, 'No elegible', $incident));
                        return;
                    }

                    $this->billSubscription($subscription, $billingPeriod, 'scheduled', $jobRun->id);
                    $jobRun->incrementInvoiced();
                } catch (\Throwable $e) {
                    $incident = $this->createIncident(
                        $jobRun,
                        $subscription,
                        BillingIncidentType::FAILED,
                        $e->getMessage(),
                        ['exception' => get_class($e), 'trace' => $e->getTraceAsString()],
                    );
                    $jobRun->incrementFailed();
                    event(new InvoiceGenerationFailed($subscription, $e, $incident));
                }
            });

            $jobRun->refresh();
            $jobRun->markCompleted();
        } catch (\Throwable $e) {
            $jobRun->markFailed($e->getMessage());
            throw $e;
        }

        event(new InvoiceBatchCompleted($jobRun->fresh()));

        return $jobRun->fresh();
    }

    public function billSubscription(
        Subscription $subscription,
        string $billingPeriod,
        string $generationSource = 'scheduled',
        ?int $jobRunId = null,
    ): Invoice {
        $context = $this->calculator->buildContext(
            $subscription,
            $billingPeriod,
            $generationSource,
            $jobRunId,
        );

        return $this->invoiceService->generateRecurringInvoice($context);
    }

    public function isEligible(Subscription $subscription, string $billingPeriod): bool
    {
        if ($subscription->status !== SubscriptionStatus::ACTIVE) {
            return false;
        }

        if (!$subscription->start_date) {
            return false;
        }

        $periodDate = Carbon::parse($billingPeriod . '-01');
        $periodEnd = $periodDate->copy()->endOfMonth();

        if ($subscription->start_date->greaterThan($periodEnd)) {
            return false;
        }

        if ($subscription->end_date && $subscription->end_date->lessThan($periodDate)) {
            return false;
        }

        $today = now();
        $billingDay = max(1, min(28, (int) $subscription->billing_day));
        $daysInMonth = $today->daysInMonth;
        $shortMonthStrategy = config('finance.billing.short_month_strategy', 'last_day');

        if ($billingDay > $daysInMonth) {
            if ($shortMonthStrategy === 'last_day') {
                // Accept: will bill on last day of month
            } elseif ($shortMonthStrategy === 'skip') {
                return false;
            }
        } elseif ($billingDay !== $today->day) {
            return false;
        }

        if ($this->hasExistingInvoice($subscription->id, $billingPeriod)) {
            return false;
        }

        if (!$subscription->customer || $subscription->customer->trashed()) {
            return false;
        }

        return true;
    }

    protected function getEligibleSubscriptions(string $billingPeriod): LazyCollection
    {
        $today = now();
        $billingDay = $today->day;
        $periodDate = Carbon::parse($billingPeriod . '-01');
        $periodStart = $periodDate->copy()->startOfMonth();
        $periodEnd = $periodDate->copy()->endOfMonth();

        return Subscription::query()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where(function ($query) use ($billingDay, $today) {
                $query->where('billing_day', $billingDay);

                // Include subscriptions with billing_day > days in month (short month rule)
                if ($billingDay === $today->daysInMonth && config('finance.billing.short_month_strategy', 'last_day') === 'last_day') {
                    $query->orWhere('billing_day', '>', $today->daysInMonth);
                }
            })
            ->whereNotNull('start_date')
            ->where('start_date', '<=', $periodEnd)
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $periodStart))
            ->whereDoesntHave('invoices', fn($q) =>
                $q->where('billing_period', $billingPeriod)
                    ->where('type', InvoiceType::MONTHLY)
                    ->whereNot('status', InvoiceStatus::CANCELLED)
            )
            ->with(['customer', 'addons', 'plan'])
            ->lazy(100);
    }

    protected function hasExistingInvoice(int $subscriptionId, string $billingPeriod): bool
    {
        return Invoice::where('subscription_id', $subscriptionId)
            ->where('billing_period', $billingPeriod)
            ->where('type', InvoiceType::MONTHLY)
            ->whereNot('status', InvoiceStatus::CANCELLED)
            ->exists();
    }

    protected function createIncident(
        BillingJobRun $jobRun,
        Subscription $subscription,
        BillingIncidentType $type,
        string $reason,
        ?array $metadata = null,
    ): BillingIncident {
        return BillingIncident::create([
            'billing_job_run_id' => $jobRun->id,
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer_id,
            'incident_type' => $type,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }
}
