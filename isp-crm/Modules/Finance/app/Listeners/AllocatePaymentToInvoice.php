<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\PaymentReceived;

class AllocatePaymentToInvoice
{
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;

        // La conciliación ya se hizo en PaymentService::registerPayment
        // Este listener sirve para lógica adicional post-conciliación si es necesaria
        Log::info("Pago #{$payment->id} recibido. Estado conciliación: {$payment->reconciliation_status->value}");
    }
}
