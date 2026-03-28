<?php

declare(strict_types=1);

namespace Modules\Subscription\Services;

use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Entities\Plan;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;
use Modules\Finance\Services\InvoiceService;
use Modules\Finance\Services\WalletService;
use Modules\Network\Services\ProvisioningService;
use Modules\Subscription\DTOs\PlanChangeCalculation;
use Modules\Subscription\DTOs\RequestPlanChangeDTO;
use Modules\Subscription\Entities\PlanChangeRequest;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionStatusHistory;
use Modules\Subscription\Enums\BillingAdjustmentType;
use Modules\Subscription\Enums\EffectiveMode;
use Modules\Subscription\Enums\PlanChangeStatus;
use Modules\Subscription\Enums\PlanChangeType;
use Modules\Subscription\Events\PlanChangeApproved;
use Modules\Subscription\Events\PlanChangeBilled;
use Modules\Subscription\Events\PlanChangeCancelled;
use Modules\Subscription\Events\PlanChangeExecuted;
use Modules\Subscription\Events\PlanChangeProvisioningFailed;
use Modules\Subscription\Events\PlanChangeRejected;
use Modules\Subscription\Events\PlanChangeRequested;
use Modules\Subscription\Events\SubscriptionPlanChanged;

class PlanChangeService
{
    public function __construct(
        protected PlanChangeCalculator $calculator,
        protected SubscriptionContractService $contractService,
        protected ProvisioningService $provisioningService,
        protected InvoiceService $invoiceService,
        protected WalletService $walletService,
    ) {}

    /**
     * Solicitar cambio de plan.
     */
    public function request(RequestPlanChangeDTO $dto): PlanChangeRequest
    {
        $subscription = Subscription::with(['plan', 'customer'])->findOrFail($dto->subscriptionId);
        $newPlan = Plan::active()->findOrFail($dto->newPlanId);

        // ── Validaciones de elegibilidad ────────────────────────
        $this->validateEligibility($subscription, $newPlan);

        // ── Calcular impacto económico ──────────────────────────
        $calculation = $this->calculator->calculate($subscription, $newPlan, $dto->effectiveMode);

        // ── Verificar factibilidad técnica ──────────────────────
        $feasibility = $this->provisioningService->validatePlanFeasibility($subscription, $newPlan);

        // ── Determinar si requiere aprobación ───────────────────
        $requiresApproval = $this->requiresApproval($calculation, $subscription);

        // ── Determinar fecha programada ─────────────────────────
        $scheduledFor = $this->resolveScheduledDate($dto, $subscription);

        // ── Crear la solicitud ──────────────────────────────────
        $request = DB::transaction(function () use (
            $subscription, $newPlan, $dto, $calculation, $feasibility, $requiresApproval, $scheduledFor
        ) {
            $planChangeRequest = PlanChangeRequest::create([
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'old_plan_id' => $subscription->plan_id,
                'new_plan_id' => $newPlan->id,
                'change_type' => $calculation->changeType,
                'effective_mode' => $dto->effectiveMode,
                'scheduled_for' => $scheduledFor,
                'status' => $requiresApproval ? PlanChangeStatus::PENDING : PlanChangeStatus::APPROVED,
                'old_plan_snapshot' => $calculation->oldPlanSnapshot,
                'new_plan_snapshot' => $calculation->newPlanSnapshot,
                'old_monthly_price' => $calculation->oldMonthlyPrice,
                'new_monthly_price' => $calculation->newMonthlyPrice,
                'prorate_credit' => $calculation->prorateCredit,
                'prorate_debit' => $calculation->prorateDebit,
                'net_difference' => $calculation->netDifference,
                'billing_adjustment_type' => $calculation->billingAdjustmentType,
                'feasibility_checked' => true,
                'feasibility_result' => $feasibility,
                'requires_approval' => $requiresApproval,
                'notes' => $dto->notes,
                'requested_by' => $dto->requestedBy,
            ]);

            $subscription->update(['has_pending_plan_change' => true]);

            return $planChangeRequest;
        });

        event(new PlanChangeRequested($request));

        // ── Auto-ejecutar si es inmediato y no requiere aprobación ─
        if (! $requiresApproval && $dto->effectiveMode === 'immediate') {
            return $this->execute($request);
        }

        return $request->fresh();
    }

    /**
     * Aprobar solicitud (si requiere aprobación).
     */
    public function approve(PlanChangeRequest $request, int $approvedBy): PlanChangeRequest
    {
        if ($request->status !== PlanChangeStatus::PENDING) {
            throw new DomainException('Solo se pueden aprobar solicitudes pendientes.');
        }

        $request->update([
            'status' => PlanChangeStatus::APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        event(new PlanChangeApproved($request->fresh()));

        // Si es inmediato, ejecutar tras aprobación
        if ($request->effective_mode === EffectiveMode::IMMEDIATE) {
            return $this->execute($request->fresh());
        }

        return $request->fresh();
    }

    /**
     * Rechazar solicitud.
     */
    public function reject(PlanChangeRequest $request, string $reason, int $rejectedBy): PlanChangeRequest
    {
        if ($request->status !== PlanChangeStatus::PENDING) {
            throw new DomainException('Solo se pueden rechazar solicitudes pendientes.');
        }

        $request->update([
            'status' => PlanChangeStatus::REJECTED,
            'rejection_reason' => $reason,
            'approved_by' => $rejectedBy,
        ]);

        $request->subscription->update(['has_pending_plan_change' => false]);

        event(new PlanChangeRejected($request->fresh(), $reason));

        return $request->fresh();
    }

    /**
     * Ejecutar el cambio de plan.
     */
    public function execute(PlanChangeRequest $request): PlanChangeRequest
    {
        if (! $request->canBeExecuted()) {
            throw new DomainException('La solicitud no puede ser ejecutada en su estado actual.');
        }

        $request->update(['status' => PlanChangeStatus::EXECUTING]);

        $subscription = $request->subscription;
        $oldPlanId = $subscription->plan_id;
        $oldPrice = (float) $subscription->monthly_price;

        try {
            DB::transaction(function () use ($request, $subscription) {
                // a. Actualizar suscripción
                $subscription->update([
                    'plan_id' => $request->new_plan_id,
                    'monthly_price' => $request->new_monthly_price,
                    'has_pending_plan_change' => false,
                    'last_plan_change_at' => now(),
                ]);

                // b. Actualizar commercial_snapshot
                $this->contractService->freezeCommercialSnapshot($subscription->fresh(['plan', 'addons', 'promotion']));

                // c. Manejar impacto financiero
                $this->handleFinancialAdjustment($request);

                // d. Registrar en historial de estado
                SubscriptionStatusHistory::create([
                    'subscription_id' => $subscription->id,
                    'from_status' => $subscription->status->value,
                    'to_status' => $subscription->status->value,
                    'reason' => 'Cambio de plan: ' . ($request->old_plan_snapshot['name'] ?? '') . ' → ' . ($request->new_plan_snapshot['name'] ?? ''),
                    'metadata' => [
                        'type' => 'plan_change',
                        'plan_change_request_id' => $request->id,
                        'old_plan_id' => $request->old_plan_id,
                        'new_plan_id' => $request->new_plan_id,
                        'change_type' => $request->change_type->value,
                        'net_difference' => (float) $request->net_difference,
                    ],
                ]);
            });

            // e. Reprovisionar servicio en red (fuera de la transacción DB)
            $this->reprovisionNetwork($request, $subscription->fresh(['plan', 'serviceInstance']));

            // f. Marcar como completado
            $request->update([
                'status' => PlanChangeStatus::COMPLETED,
                'effective_at' => now(),
                'executed_at' => now(),
            ]);

            event(new PlanChangeExecuted($request->fresh()));
            event(new SubscriptionPlanChanged(
                $subscription->fresh(),
                $request->old_plan_snapshot,
                $request->new_plan_snapshot,
            ));

        } catch (\Throwable $e) {
            // Rollback: restaurar plan anterior
            $subscription->update([
                'plan_id' => $request->old_plan_id,
                'monthly_price' => $request->old_monthly_price,
                'has_pending_plan_change' => false,
            ]);

            $request->update([
                'status' => PlanChangeStatus::FAILED,
                'provision_status' => 'failed',
                'provision_result' => [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toIso8601String(),
                ],
            ]);

            Log::error('Plan change execution failed', [
                'plan_change_request_id' => $request->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            event(new PlanChangeProvisioningFailed($request->fresh(), $e->getMessage()));
        }

        return $request->fresh();
    }

    /**
     * Cancelar solicitud pendiente.
     */
    public function cancel(PlanChangeRequest $request, string $reason): PlanChangeRequest
    {
        if (! $request->canBeCancelled()) {
            throw new DomainException('La solicitud no puede ser cancelada en su estado actual.');
        }

        $request->update([
            'status' => PlanChangeStatus::CANCELLED,
            'notes' => $request->notes ? $request->notes . "\nCancelación: {$reason}" : "Cancelación: {$reason}",
        ]);

        $request->subscription->update(['has_pending_plan_change' => false]);

        event(new PlanChangeCancelled($request->fresh(), $reason));

        return $request->fresh();
    }

    /**
     * Preview: calcular impacto sin ejecutar.
     */
    public function preview(int $subscriptionId, int $newPlanId): PlanChangeCalculation
    {
        $subscription = Subscription::with(['plan'])->findOrFail($subscriptionId);
        $newPlan = Plan::active()->findOrFail($newPlanId);

        if ($subscription->plan_id === $newPlan->id) {
            throw new DomainException('El plan destino es igual al plan actual.');
        }

        $effectiveMode = $this->resolveDefaultEffectiveMode(
            PlanChangeType::determine((float) $subscription->monthly_price, (float) $newPlan->price)
        );

        return $this->calculator->calculate($subscription, $newPlan, $effectiveMode);
    }

    /**
     * Procesar cambios programados cuya fecha llegó.
     */
    public function processScheduledChanges(): int
    {
        $requests = PlanChangeRequest::with(['subscription'])
            ->scheduledFor(Carbon::today())
            ->get();

        $processed = 0;

        foreach ($requests as $request) {
            try {
                $this->execute($request);
                $processed++;
            } catch (\Throwable $e) {
                Log::error('Failed to process scheduled plan change', [
                    'plan_change_request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    // ── Métodos protegidos ──────────────────────────────────────

    protected function validateEligibility(Subscription $subscription, Plan $newPlan): void
    {
        if (! $subscription->isActive()) {
            throw new DomainException('La suscripción debe estar activa para cambiar de plan.');
        }

        if ($subscription->hasPendingPlanChange()) {
            throw new DomainException('Ya existe una solicitud de cambio de plan pendiente.');
        }

        if ($subscription->plan_id === $newPlan->id) {
            throw new DomainException('El plan destino es igual al plan actual.');
        }

        // Permanencia mínima: bloquea downgrades y laterales, permite upgrades
        if ($subscription->isWithinMinimumStay()) {
            $changeType = PlanChangeType::determine(
                (float) $subscription->monthly_price,
                (float) $newPlan->price,
            );

            if ($changeType !== PlanChangeType::UPGRADE) {
                throw new DomainException(
                    'La suscripción está en período de permanencia mínima hasta '
                    . $subscription->minimum_stay_until->format('d/m/Y')
                    . '. Solo se permiten upgrades.'
                );
            }
        }
    }

    protected function requiresApproval(PlanChangeCalculation $calculation, Subscription $subscription): bool
    {
        // Downgrade durante promoción activa
        if (
            $calculation->changeType === 'downgrade'
            && $subscription->discount_months_remaining > 0
            && config('subscription.plan_change.require_approval_during_promotion', true)
        ) {
            return true;
        }

        // Downgrade con aprobación configurada
        if (
            $calculation->changeType === 'downgrade'
            && config('subscription.plan_change.require_approval_for_downgrade', false)
        ) {
            return true;
        }

        return false;
    }

    protected function resolveScheduledDate(RequestPlanChangeDTO $dto, Subscription $subscription): ?string
    {
        if ($dto->effectiveMode === 'scheduled' && $dto->scheduledFor) {
            return $dto->scheduledFor->toDateString();
        }

        if ($dto->effectiveMode === 'next_cycle') {
            return $subscription->getNextBillingDate()->toDateString();
        }

        return null;
    }

    protected function resolveDefaultEffectiveMode(PlanChangeType $changeType): string
    {
        if ($changeType === PlanChangeType::UPGRADE) {
            return config('subscription.plan_change.upgrade_mode', 'immediate');
        }

        if ($changeType === PlanChangeType::DOWNGRADE) {
            return config('subscription.plan_change.downgrade_mode', 'next_cycle');
        }

        return config('subscription.plan_change.default_effective_mode', 'immediate');
    }

    protected function handleFinancialAdjustment(PlanChangeRequest $request): void
    {
        $adjustmentType = $request->billing_adjustment_type;
        $netDifference = (float) $request->net_difference;

        if ($adjustmentType === BillingAdjustmentType::NONE || $netDifference == 0) {
            return;
        }

        if ($adjustmentType === BillingAdjustmentType::INVOICE && $netDifference > 0) {
            $invoice = $this->createAdjustmentInvoice($request);
            event(new PlanChangeBilled($request, $invoice));
            return;
        }

        if (
            $adjustmentType === BillingAdjustmentType::WALLET_CREDIT
            && $netDifference < 0
        ) {
            $transaction = $this->walletService->credit(
                $request->customer_id,
                abs($netDifference),
                'Crédito por cambio de plan',
                'Prorrateo por cambio: ' . ($request->old_plan_snapshot['name'] ?? '') . ' → ' . ($request->new_plan_snapshot['name'] ?? ''),
                PlanChangeRequest::class,
                $request->id,
            );
            event(new PlanChangeBilled($request, $transaction));
        }
    }

    protected function createAdjustmentInvoice(PlanChangeRequest $request): Invoice
    {
        $gracePeriod = (int) config('finance.billing.grace_period_days', 10);
        $year = now()->year;
        $last = Invoice::whereYear('created_at', $year)->orderByDesc('id')->first();
        $next = $last ? $last->id + 1 : 1;
        $invoiceNumber = 'FAC-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);

        $netDifference = (float) $request->net_difference;

        $invoice = Invoice::create([
            'customer_id' => $request->customer_id,
            'subscription_id' => $request->subscription_id,
            'invoice_number' => $invoiceNumber,
            'type' => InvoiceType::ADJUSTMENT,
            'subtotal' => $netDifference,
            'tax' => 0,
            'total' => $netDifference,
            'balance_due' => $netDifference,
            'due_date' => now()->addDays($gracePeriod),
            'status' => InvoiceStatus::ISSUED,
            'metadata' => [
                'plan_change_request_id' => $request->id,
                'old_plan' => $request->old_plan_snapshot['name'] ?? null,
                'new_plan' => $request->new_plan_snapshot['name'] ?? null,
                'prorate_credit' => (float) $request->prorate_credit,
                'prorate_debit' => (float) $request->prorate_debit,
            ],
        ]);

        $invoice->items()->create([
            'concept' => 'Ajuste por cambio de plan',
            'description' => 'Diferencia prorrateada: '
                . ($request->old_plan_snapshot['name'] ?? '') . ' → '
                . ($request->new_plan_snapshot['name'] ?? ''),
            'quantity' => 1,
            'unit_price' => $netDifference,
            'subtotal' => $netDifference,
            'tax' => 0,
        ]);

        return $invoice->fresh(['items']);
    }

    protected function reprovisionNetwork(PlanChangeRequest $request, Subscription $subscription): void
    {
        $oldSnapshot = $request->old_plan_snapshot;
        $newSnapshot = $request->new_plan_snapshot;

        // Solo reprovisionar si cambian parámetros de red
        $networkChanged = ($oldSnapshot['router_profile'] ?? null) !== ($newSnapshot['router_profile'] ?? null)
            || ($oldSnapshot['olt_profile'] ?? null) !== ($newSnapshot['olt_profile'] ?? null)
            || ($oldSnapshot['ip_pool_id'] ?? null) !== ($newSnapshot['ip_pool_id'] ?? null)
            || ($oldSnapshot['download_speed'] ?? null) !== ($newSnapshot['download_speed'] ?? null)
            || ($oldSnapshot['upload_speed'] ?? null) !== ($newSnapshot['upload_speed'] ?? null);

        if (! $networkChanged) {
            $request->update([
                'provision_status' => 'success',
                'provision_result' => ['message' => 'No requiere reprovisión de red'],
            ]);
            return;
        }

        try {
            $result = $this->provisioningService->provisionSubscription($subscription);
            $request->update([
                'provision_status' => 'success',
                'provision_result' => [
                    'message' => 'Reprovisión exitosa',
                    'result' => method_exists($result, 'toArray') ? $result->toArray() : (array) $result,
                    'completed_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            $request->update([
                'provision_status' => 'failed',
                'provision_result' => [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toIso8601String(),
                ],
            ]);
            throw $e;
        }
    }
}
