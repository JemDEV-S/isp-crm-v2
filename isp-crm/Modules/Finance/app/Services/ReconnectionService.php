<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Entities\CollectionCase;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Events\ReconnectionCompleted;
use Modules\Finance\Events\ReconnectionFailed;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Services\SubscriptionService;

class ReconnectionService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) {}

    public function evaluateReconnection(Invoice $paidInvoice): ?Subscription
    {
        $subscription = $paidInvoice->subscription;

        if (! $subscription) {
            return null;
        }

        // Solo evaluar si la suscripción está suspendida
        if (! $subscription->isSuspended()) {
            return null;
        }

        // Verificar si hay deuda residual
        if ($this->hasBlockingDebt($subscription)) {
            return null;
        }

        // Verificar bloqueos no financieros
        if ($this->hasNonFinancialBlocks($subscription)) {
            return null;
        }

        // Auto-reconexión habilitada?
        if (! config('finance.payments.auto_reconnect', true)) {
            return null;
        }

        return $this->reconnect($subscription, 'Pago completo recibido - reconexión automática');
    }

    public function hasBlockingDebt(Subscription $subscription): bool
    {
        return Invoice::where('subscription_id', $subscription->id)
            ->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::CANCELLED])
            ->where('balance_due', '>', 0)
            ->where('due_date', '<', now())
            ->exists();
    }

    public function hasNonFinancialBlocks(Subscription $subscription): bool
    {
        // Verificar si hay un caso de cobranza abierto
        $hasOpenCase = CollectionCase::where('subscription_id', $subscription->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->exists();

        if ($hasOpenCase) {
            return true;
        }

        // Verificar bloqueo manual en metadata
        $metadata = $subscription->metadata ?? [];
        if (! empty($metadata['manual_block'])) {
            return true;
        }

        return false;
    }

    public function reconnect(Subscription $subscription, string $reason): ?Subscription
    {
        try {
            $this->subscriptionService->reactivate($subscription);

            Log::info("Reconexión exitosa para suscripción #{$subscription->id}: {$reason}");

            event(new ReconnectionCompleted($subscription->fresh()));

            return $subscription->fresh();
        } catch (\Throwable $e) {
            Log::error("Fallo en reconexión para suscripción #{$subscription->id}: {$e->getMessage()}");

            event(new ReconnectionFailed($subscription, $e->getMessage()));

            // NO revertir el pago — crear incidencia operativa
            return null;
        }
    }
}
