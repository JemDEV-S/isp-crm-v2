<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Entities\BillingIncident;
use Modules\Finance\Entities\BillingJobRun;
use Modules\Finance\Jobs\GenerateMonthlyInvoicesJob;
use Modules\Finance\Services\BillingCalculator;
use Modules\Finance\Services\RecurringBillingService;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Enums\SubscriptionStatus;

class BillingController extends Controller
{
    public function __construct(
        protected RecurringBillingService $billingService,
        protected BillingCalculator $calculator,
    ) {}

    public function run(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|regex:/^\d{4}-\d{2}$/',
            'sync' => 'sometimes|boolean',
        ]);

        $period = $request->input('period', now()->format('Y-m'));

        if ($request->boolean('sync')) {
            $jobRun = $this->billingService->runBillingCycle($period, 'manual', auth()->id());

            return response()->json([
                'message' => 'Ciclo de facturacion completado',
                'data' => $jobRun,
            ]);
        }

        GenerateMonthlyInvoicesJob::dispatch($period, 'manual', auth()->id());

        return response()->json([
            'message' => "Job de facturacion encolado para periodo {$period}",
        ], 202);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $period = $request->input('period', now()->format('Y-m'));
        $today = now();

        $subscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('billing_day', $today->day)
            ->whereNotNull('start_date')
            ->with(['customer', 'plan', 'addons'])
            ->get();

        $previews = $subscriptions->map(function (Subscription $sub) use ($period) {
            $eligible = $this->billingService->isEligible($sub, $period);
            $context = $eligible ? $this->calculator->buildContext($sub, $period, 'manual') : null;

            return [
                'subscription_id' => $sub->id,
                'customer_name' => $sub->customer->full_name ?? $sub->customer->name ?? 'N/A',
                'plan' => $sub->plan->name ?? 'N/A',
                'eligible' => $eligible,
                'base_price' => $context?->basePrice,
                'discount' => $context?->discountAmount,
                'addons_total' => $context?->addonsTotal,
                'subtotal' => $context?->subtotal,
                'tax' => $context?->taxAmount,
                'total' => $context?->total,
            ];
        });

        return response()->json([
            'data' => [
                'period' => $period,
                'total_subscriptions' => $subscriptions->count(),
                'total_eligible' => $previews->where('eligible', true)->count(),
                'total_amount' => $previews->where('eligible', true)->sum('total'),
                'subscriptions' => $previews,
            ],
        ]);
    }

    public function jobRuns(Request $request): JsonResponse
    {
        $runs = BillingJobRun::query()
            ->when($request->input('billing_period'), fn($q, $v) => $q->where('billing_period', $v))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $runs]);
    }

    public function jobRunShow(BillingJobRun $jobRun): JsonResponse
    {
        return response()->json([
            'data' => $jobRun->load(['incidents.subscription', 'incidents.customer']),
        ]);
    }

    public function incidents(Request $request): JsonResponse
    {
        $incidents = BillingIncident::query()
            ->with(['subscription', 'customer', 'jobRun'])
            ->when($request->input('type'), fn($q, $v) => $q->where('incident_type', $v))
            ->when($request->boolean('pending_only'), fn($q) => $q->whereNull('resolved_at'))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $incidents]);
    }

    public function resolveIncident(BillingIncident $incident): JsonResponse
    {
        if ($incident->isResolved()) {
            return response()->json(['message' => 'El incidente ya fue resuelto'], 422);
        }

        $incident->resolve(auth()->id());

        return response()->json([
            'message' => 'Incidente resuelto',
            'data' => $incident->fresh(),
        ]);
    }
}
