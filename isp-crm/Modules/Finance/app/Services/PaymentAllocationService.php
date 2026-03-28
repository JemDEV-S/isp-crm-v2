<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Entities\PaymentAllocation;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\ReconciliationStatus;
use Modules\Finance\Enums\WalletConcept;
use Modules\Finance\Events\InvoicePaid;
use Modules\Finance\Events\InvoicePartiallyPaid;

class PaymentAllocationService
{
    public function __construct(
        protected WalletService $walletService,
    ) {}

    public function allocateToInvoice(Payment $payment, Invoice $invoice, ?float $amount = null): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $invoice, $amount) {
            $remaining = $payment->getRemainingAmount();
            $balanceDue = (float) $invoice->balance_due;
            $allocateAmount = $amount !== null
                ? min($amount, $remaining, $balanceDue)
                : min($remaining, $balanceDue);

            if ($allocateAmount <= 0) {
                throw new \RuntimeException('No hay monto disponible para asignar.');
            }

            $allocation = PaymentAllocation::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $allocateAmount,
                'allocated_at' => now(),
                'allocated_by' => 'system',
            ]);

            $this->recalculateInvoiceStatus($invoice);
            $this->updatePaymentReconciliationStatus($payment);

            // Si queda excedente después de asignar y el pago ya está totalmente conciliado
            // el excedente se maneja en allocateToOldestInvoices o en PaymentService
            return $allocation;
        });
    }

    public function allocateToOldestInvoices(Payment $payment): array
    {
        $allocations = [];

        $invoices = Invoice::where('customer_id', $payment->customer_id)
            ->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::CANCELLED])
            ->where('balance_due', '>', 0)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $invoice) {
            if ($payment->getRemainingAmount() <= 0) {
                break;
            }

            $allocations[] = $this->allocateToInvoice($payment, $invoice);
            $payment->refresh();
        }

        // Si queda excedente después de conciliar todas las facturas
        $excess = $payment->getRemainingAmount();
        if ($excess > 0) {
            $this->handleExcess($payment, $excess);
        }

        return $allocations;
    }

    public function recalculateInvoiceStatus(Invoice $invoice): void
    {
        $invoice->recalculateTotals();
        $invoice->refresh();

        $totalPaid = (float) $invoice->total_paid;
        $total = (float) $invoice->total;

        if ($totalPaid >= $total) {
            $invoice->update([
                'status' => InvoiceStatus::PAID,
                'paid_at' => now(),
            ]);
            event(new InvoicePaid($invoice->fresh()));
        } elseif ($totalPaid > 0) {
            $invoice->update([
                'status' => InvoiceStatus::PARTIALLY_PAID,
            ]);
            event(new InvoicePartiallyPaid($invoice->fresh(), $invoice->balance_due));
        }
    }

    public function handleExcess(Payment $payment, float $excess): void
    {
        if ($excess <= 0) {
            return;
        }

        if (! config('finance.payments.excess_to_wallet', true)) {
            return;
        }

        $this->walletService->credit(
            $payment->customer_id,
            $excess,
            WalletConcept::PAYMENT_EXCESS->value,
            "Excedente del pago #{$payment->id}",
            Payment::class,
            $payment->id,
        );
    }

    public function getUnmatchedPayments(array $filters = []): LengthAwarePaginator
    {
        $query = Payment::where('reconciliation_status', ReconciliationStatus::UNMATCHED)
            ->with(['customer'])
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('received_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('received_at', '<=', $v))
            ->orderByDesc('received_at');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function manualAllocate(int $paymentId, int $invoiceId, float $amount): PaymentAllocation
    {
        $payment = Payment::findOrFail($paymentId);
        $invoice = Invoice::findOrFail($invoiceId);

        return DB::transaction(function () use ($payment, $invoice, $amount) {
            $allocation = $this->allocateToInvoice($payment, $invoice, $amount);
            $allocation->update(['allocated_by' => 'manual']);

            return $allocation;
        });
    }

    protected function updatePaymentReconciliationStatus(Payment $payment): void
    {
        $payment->refresh();

        if ($payment->isFullyAllocated()) {
            $payment->update(['reconciliation_status' => ReconciliationStatus::ALLOCATED]);
        } else {
            $allocated = (float) $payment->allocations()->sum('amount');
            $status = $allocated > 0
                ? ReconciliationStatus::PARTIALLY_ALLOCATED
                : ReconciliationStatus::PENDING;
            $payment->update(['reconciliation_status' => $status]);
        }
    }
}
