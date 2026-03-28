<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\DTOs\RegisterPaymentDTO;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Enums\ReconciliationStatus;
use Modules\Finance\Events\PaymentReceived;
use Modules\Finance\Events\PaymentReversed;
use Modules\Finance\Events\PaymentValidated;

class PaymentService
{
    public function __construct(
        protected PaymentAllocationService $allocationService,
        protected WalletService $walletService,
        protected ReconnectionService $reconnectionService,
    ) {}

    public function registerPayment(RegisterPaymentDTO $dto): Payment
    {
        // Verificar idempotencia
        if ($dto->idempotencyKey) {
            $existing = Payment::where('idempotency_key', $dto->idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($dto) {
            $payment = Payment::create([
                'uuid' => (string) Str::uuid(),
                'customer_id' => $dto->customerId,
                'amount' => $dto->amount,
                'currency' => config('finance.payments.default_currency', 'PEN'),
                'method' => $dto->method,
                'channel' => $dto->channel,
                'status' => PaymentStatus::COMPLETED,
                'reference' => $dto->reference,
                'external_id' => $dto->externalId,
                'idempotency_key' => $dto->idempotencyKey,
                'gateway_response' => $dto->gatewayResponse,
                'received_at' => $dto->receivedAt ?? now(),
                'reconciliation_status' => ReconciliationStatus::PENDING,
                'notes' => $dto->notes,
                'processed_by' => $dto->processedBy ?? auth()->id(),
            ]);

            // Conciliar
            if ($dto->invoiceId) {
                $invoice = Invoice::findOrFail($dto->invoiceId);
                $this->allocationService->allocateToInvoice($payment, $invoice);

                // Si queda excedente, conciliar contra las más antiguas
                $payment->refresh();
                if ($payment->getRemainingAmount() > 0 && config('finance.payments.auto_allocate', true)) {
                    $this->allocationService->allocateToOldestInvoices($payment);
                }
            } elseif (config('finance.payments.auto_allocate', true)) {
                // Intentar match automático
                $this->allocationService->allocateToOldestInvoices($payment);
            }

            // Actualizar estado de reconciliación si no se pudo conciliar
            $payment->refresh();
            if ($payment->reconciliation_status === ReconciliationStatus::PENDING) {
                $allocated = (float) $payment->allocations()->sum('amount');
                if ($allocated == 0) {
                    $payment->update(['reconciliation_status' => ReconciliationStatus::UNMATCHED]);
                }
            }

            event(new PaymentReceived($payment->fresh()));

            return $payment->fresh();
        });
    }

    public function payInvoice(int $invoiceId, RegisterPaymentDTO $dto): Payment
    {
        $dto = new RegisterPaymentDTO(
            customerId: $dto->customerId,
            amount: $dto->amount,
            method: $dto->method,
            channel: $dto->channel,
            invoiceId: $invoiceId,
            reference: $dto->reference,
            externalId: $dto->externalId,
            idempotencyKey: $dto->idempotencyKey,
            gatewayResponse: $dto->gatewayResponse,
            receivedAt: $dto->receivedAt,
            processedBy: $dto->processedBy,
            notes: $dto->notes,
        );

        return $this->registerPayment($dto);
    }

    public function processWebhook(string $gateway, array $payload, string $signature, string $ip): Payment
    {
        $webhookService = app(PaymentWebhookService::class);
        $log = $webhookService->process($gateway, $payload, $signature, $ip);

        if ($log->payment_id) {
            return Payment::find($log->payment_id);
        }

        throw new \RuntimeException("Webhook no pudo ser procesado: {$log->processing_result}");
    }

    public function validatePayment(Payment $payment): Payment
    {
        $payment->update([
            'status' => PaymentStatus::VALIDATED,
            'validated_at' => now(),
        ]);

        event(new PaymentValidated($payment->fresh()));

        return $payment->fresh();
    }

    public function reversePayment(Payment $payment, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            // Revertir allocations
            foreach ($payment->allocations as $allocation) {
                $invoice = $allocation->invoice;
                $allocation->delete();
                $invoice->recalculateTotals();

                // Restaurar estado de factura
                $invoice->refresh();
                if ((float) $invoice->total_paid === 0.0) {
                    $previousStatus = $invoice->due_date < now()
                        ? 'overdue'
                        : 'issued';
                    $invoice->update([
                        'status' => $previousStatus,
                        'paid_at' => null,
                    ]);
                } else {
                    $invoice->update(['status' => 'partially_paid']);
                }
            }

            $payment->update([
                'status' => PaymentStatus::REVERSED,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'reversal_reason' => $reason,
                    'reversed_at' => now()->toIso8601String(),
                ]),
            ]);

            event(new PaymentReversed($payment->fresh(), $reason));

            return $payment->fresh();
        });
    }
}
