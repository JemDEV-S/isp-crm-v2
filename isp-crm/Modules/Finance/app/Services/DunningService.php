<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\DTOs\DunningRunResult;
use Modules\Finance\Entities\DunningExecution;
use Modules\Finance\Entities\DunningPolicy;
use Modules\Finance\Entities\DunningStage;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\AgingBucket;
use Modules\Finance\Enums\CollectionCaseStatus;
use Modules\Finance\Enums\DunningActionType;
use Modules\Finance\Events\DunningStageTriggered;
use Modules\Finance\Events\SubscriptionSuspendedForDebt;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Services\SubscriptionService;

class DunningService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected InvoiceService $invoiceService,
    ) {}

    public function processAll(string $jobRunId): DunningRunResult
    {
        $totalProcessed = 0;
        $totalExecuted = 0;
        $totalSkipped = 0;
        $totalFailed = 0;
        $totalOutOfRange = 0;

        $invoices = Invoice::dunningEligible()
            ->with(['subscription', 'subscription.promisesToPay'])
            ->cursor();

        foreach ($invoices as $invoice) {
            $totalProcessed++;

            try {
                $execution = $this->processInvoice($invoice, $jobRunId);

                if ($execution === null) {
                    $totalOutOfRange++;
                } elseif ($execution->wasSkipped()) {
                    $totalSkipped++;
                } else {
                    $totalExecuted++;
                }
            } catch (\Throwable $e) {
                $totalFailed++;
                Log::error('Dunning processing failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return new DunningRunResult(
            jobRunId: $jobRunId,
            totalProcessed: $totalProcessed,
            totalExecuted: $totalExecuted,
            totalSkipped: $totalSkipped,
            totalFailed: $totalFailed,
            totalOutOfRange: $totalOutOfRange,
        );
    }

    public function processInvoice(Invoice $invoice, string $jobRunId): ?DunningExecution
    {
        $daysOverdue = (int) Carbon::parse($invoice->due_date)->diffInDays(now(), false);
        if ($daysOverdue < 0) {
            $daysOverdue = 0;
        }

        // Actualizar aging
        $invoice->update([
            'days_overdue' => $daysOverdue,
            'aging_bucket' => AgingBucket::fromDays($daysOverdue),
        ]);

        // Resolver política
        $subscription = $invoice->subscription;
        if (!$subscription) {
            return null;
        }

        $policy = $this->resolvePolicy($subscription);
        $stage = $this->resolveStage($policy, $daysOverdue);

        if (!$stage) {
            return null;
        }

        // Idempotencia: verificar si ya se ejecutó esta stage para esta factura
        $alreadyExecuted = DunningExecution::where('invoice_id', $invoice->id)
            ->where('dunning_stage_id', $stage->id)
            ->exists();

        if ($alreadyExecuted) {
            return null;
        }

        // Verificar exclusiones
        $exclusions = $this->getExclusions($invoice);
        if (!empty($exclusions)) {
            $skipReason = $exclusions[0];

            return DunningExecution::create([
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
                'customer_id' => $invoice->customer_id,
                'dunning_stage_id' => $stage->id,
                'action_type' => $stage->action_type,
                'status' => 'skipped',
                'skip_reason' => $skipReason,
                'days_overdue' => $daysOverdue,
                'amount_overdue' => $invoice->total,
                'executed_by' => 'job',
                'job_run_id' => $jobRunId,
                'executed_at' => now(),
            ]);
        }

        // Ejecutar acción
        return $this->executeAction($invoice, $stage, $jobRunId);
    }

    public function isEligible(Invoice $invoice): bool
    {
        if ($invoice->isPaid()) {
            return false;
        }

        if ($invoice->dunning_paused) {
            return false;
        }

        if ($invoice->due_date->gte(now())) {
            return false;
        }

        $exclusions = $this->getExclusions($invoice);

        return empty($exclusions);
    }

    public function getExclusions(Invoice $invoice): array
    {
        $exclusions = [];

        if ($invoice->dunning_paused) {
            $exclusions[] = 'manual_block';
        }

        if ($invoice->subscription && $invoice->subscription->hasActivePromise()) {
            $exclusions[] = 'promise_active';
        }

        if ($invoice->disputes()->whereIn('status', ['open', 'under_review'])->exists()) {
            $exclusions[] = 'dispute_open';
        }

        return $exclusions;
    }

    public function resolveStage(DunningPolicy $policy, int $daysOverdue): ?DunningStage
    {
        return $policy->stages()
            ->where('min_days_overdue', '<=', $daysOverdue)
            ->where('max_days_overdue', '>=', $daysOverdue)
            ->first();
    }

    public function resolvePolicy(Subscription $subscription): DunningPolicy
    {
        // Intentar buscar política específica por segmento/zona
        $specific = DunningPolicy::active()
            ->where('applies_to', 'plan')
            ->where('applies_to_value', $subscription->plan_id)
            ->first();

        if ($specific) {
            return $specific;
        }

        // Política por defecto
        $default = DunningPolicy::active()->default()->first();

        if ($default) {
            return $default;
        }

        // Fallback: buscar por código de config
        $code = config('finance.dunning.default_policy', 'standard_residential');

        return DunningPolicy::where('code', $code)->firstOrFail();
    }

    protected function executeAction(Invoice $invoice, DunningStage $stage, string $jobRunId): DunningExecution
    {
        $result = null;
        $status = 'executed';
        $channel = $stage->channels[0] ?? 'system';

        try {
            match ($stage->action_type) {
                DunningActionType::REMINDER,
                DunningActionType::WARNING,
                DunningActionType::PRE_TERMINATION => $result = "Notificación programada por canal: {$channel}",

                DunningActionType::SUSPENSION => $this->executeSuspension($invoice),

                DunningActionType::WRITE_OFF,
                DunningActionType::EXTERNAL_COLLECTION => $this->createCollectionCase($invoice),
            };

            if ($stage->action_type === DunningActionType::SUSPENSION) {
                $result = 'Servicio suspendido por mora';
            } elseif (in_array($stage->action_type, [DunningActionType::WRITE_OFF, DunningActionType::EXTERNAL_COLLECTION])) {
                $result = 'Caso de cobranza creado';
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $result = $e->getMessage();
            Log::error('Dunning action failed', [
                'invoice_id' => $invoice->id,
                'stage' => $stage->code,
                'error' => $e->getMessage(),
            ]);
        }

        $execution = DunningExecution::create([
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription_id,
            'customer_id' => $invoice->customer_id,
            'dunning_stage_id' => $stage->id,
            'action_type' => $stage->action_type,
            'channel' => $channel,
            'status' => $status,
            'result' => $result,
            'days_overdue' => $invoice->days_overdue,
            'amount_overdue' => $invoice->total,
            'executed_by' => 'job',
            'job_run_id' => $jobRunId,
            'executed_at' => now(),
        ]);

        // Actualizar referencia en la factura
        $invoice->update(['last_dunning_stage_id' => $stage->id]);

        // Disparar evento
        if ($status === 'executed') {
            event(new DunningStageTriggered($execution, $invoice));
        }

        return $execution;
    }

    protected function executeSuspension(Invoice $invoice): void
    {
        $subscription = $invoice->subscription;

        if (!$subscription->canBeSuspended()) {
            throw new \DomainException('La suscripción no puede ser suspendida');
        }

        if ($subscription->hasActivePromise()) {
            throw new \DomainException('La suscripción tiene una promesa de pago activa');
        }

        if ($invoice->disputes()->whereIn('status', ['open', 'under_review'])->exists()) {
            throw new \DomainException('La factura tiene una disputa abierta');
        }

        $this->subscriptionService->suspend($subscription, 'Suspensión por mora automática');

        event(new SubscriptionSuspendedForDebt($subscription, $invoice));
    }

    protected function createCollectionCase(Invoice $invoice): void
    {
        $subscription = $invoice->subscription;

        // Verificar si ya hay un caso abierto
        $existingCase = $subscription?->collectionCases()
            ->whereIn('status', [
                CollectionCaseStatus::OPEN->value,
                CollectionCaseStatus::IN_PROGRESS->value,
            ])
            ->exists();

        if ($existingCase) {
            return;
        }

        app(CollectionCaseService::class)->open(
            customerId: $invoice->customer_id,
            subscriptionId: $invoice->subscription_id,
            totalDebt: (float) $invoice->total,
        );
    }
}
