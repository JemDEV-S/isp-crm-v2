<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Enums\PaymentChannel;
use Modules\Finance\Events\PaymentReceived;

class LogWebhookResult
{
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;

        if ($payment->channel !== PaymentChannel::WEBHOOK) {
            return;
        }

        Log::info("Pago por webhook procesado: Pago #{$payment->id} - Gateway: {$payment->external_id} - Monto: {$payment->amount}");
    }
}
