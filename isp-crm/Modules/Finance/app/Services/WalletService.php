<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Crm\Entities\Customer;
use Modules\Finance\DTOs\RegisterPaymentDTO;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Entities\Wallet;
use Modules\Finance\Entities\WalletTransaction;
use Modules\Finance\Enums\PaymentChannel;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\WalletConcept;
use Modules\Finance\Events\WalletCredited;
use Modules\Finance\Events\WalletDebited;

class WalletService
{
    public function createForCustomer(Customer $customer): Wallet
    {
        return Wallet::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'balance' => 0,
                'credit_limit' => (float) ($customer->credit_limit ?? 0),
                'status' => 'active',
            ]
        );
    }

    public function getOrCreateForCustomer(int $customerId): Wallet
    {
        $customer = Customer::findOrFail($customerId);

        return $this->createForCustomer($customer);
    }

    public function credit(
        int $customerId,
        float $amount,
        string $concept,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransaction {
        $wallet = $this->getOrCreateForCustomer($customerId);

        $transaction = $wallet->credit($amount, $concept, $description, $referenceType, $referenceId);

        event(new WalletCredited($transaction, $amount, $concept));

        return $transaction;
    }

    public function debit(
        int $customerId,
        float $amount,
        string $concept,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransaction {
        $wallet = $this->getOrCreateForCustomer($customerId);

        $transaction = $wallet->debit($amount, $concept, $description, $referenceType, $referenceId);

        event(new WalletDebited($transaction, $amount, $concept));

        return $transaction;
    }

    public function getBalance(int $customerId): float
    {
        $wallet = $this->getOrCreateForCustomer($customerId);

        return (float) $wallet->balance;
    }

    public function getTransactions(int $customerId, array $filters = []): LengthAwarePaginator
    {
        $wallet = $this->getOrCreateForCustomer($customerId);

        $query = $wallet->transactions()
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['concept'] ?? null, fn ($q, $v) => $q->where('concept', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v))
            ->orderByDesc('created_at');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function payFromWallet(int $customerId, int $invoiceId): ?Payment
    {
        $wallet = $this->getOrCreateForCustomer($customerId);
        $invoice = Invoice::findOrFail($invoiceId);
        $balanceDue = (float) $invoice->balance_due;

        if ($balanceDue <= 0) {
            return null;
        }

        $available = (float) $wallet->balance;
        if ($available <= 0) {
            return null;
        }

        $payAmount = min($available, $balanceDue);

        return DB::transaction(function () use ($customerId, $invoiceId, $payAmount) {
            // Debitar wallet
            $this->debit(
                $customerId,
                $payAmount,
                WalletConcept::PAYMENT_FROM_WALLET->value,
                "Pago de factura #{$invoiceId} desde wallet",
                Invoice::class,
                $invoiceId,
            );

            // Crear pago a través del PaymentService
            $paymentService = app(PaymentService::class);

            return $paymentService->registerPayment(new RegisterPaymentDTO(
                customerId: $customerId,
                amount: $payAmount,
                method: PaymentMethod::WALLET->value,
                channel: PaymentChannel::WALLET->value,
                invoiceId: $invoiceId,
                idempotencyKey: 'wallet:' . $customerId . ':' . $invoiceId . ':' . now()->timestamp,
            ));
        });
    }
}
