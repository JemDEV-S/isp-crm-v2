<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\InvoiceGenerated;
use Modules\Finance\Services\WalletService;

class UpdateDebtAging
{
    public function __construct(
        protected WalletService $walletService,
    ) {}

    public function handle(InvoiceGenerated $event): void
    {
        $invoice = $event->invoice;

        try {
            $wallet = $this->walletService->getOrCreateForCustomer($invoice->customer_id);

            $wallet->update([
                'balance' => $wallet->balance - (float) $invoice->total,
            ]);

            Log::info("Wallet del customer #{$invoice->customer_id} actualizada. Nuevo balance: {$wallet->fresh()->balance}");
        } catch (\Throwable $e) {
            Log::error("Error actualizando wallet para factura #{$invoice->invoice_number}: {$e->getMessage()}");
        }
    }
}
