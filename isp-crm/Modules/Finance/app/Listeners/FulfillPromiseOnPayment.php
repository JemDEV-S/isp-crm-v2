<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Entities\PromiseToPay;
use Modules\Finance\Enums\PromiseStatus;
use Modules\Finance\Events\InvoicePaid;
use Modules\Finance\Events\PromiseToPayFulfilled;

class FulfillPromiseOnPayment
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        // Buscar promesas de pago pendientes para esta factura
        $promises = PromiseToPay::where('invoice_id', $invoice->id)
            ->where('status', PromiseStatus::PENDING)
            ->get();

        foreach ($promises as $promise) {
            $promise->update([
                'status' => PromiseStatus::FULFILLED,
                'fulfilled_at' => now(),
            ]);

            event(new PromiseToPayFulfilled($promise));

            Log::info("Promesa de pago #{$promise->id} cumplida por pago de factura #{$invoice->id}");
        }
    }
}
