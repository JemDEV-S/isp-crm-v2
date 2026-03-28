<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\PaymentReceived;

class NotifyPaymentReceived
{
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;

        // TODO: Implementar notificación por email/SMS al cliente
        Log::info("Notificación de pago recibido: Pago #{$payment->id} - Cliente #{$payment->customer_id} - Monto: {$payment->amount}");
    }
}
