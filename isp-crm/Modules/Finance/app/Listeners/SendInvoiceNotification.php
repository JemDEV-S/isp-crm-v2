<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\InvoiceGenerated;

class SendInvoiceNotification
{
    public function handle(InvoiceGenerated $event): void
    {
        $invoice = $event->invoice;
        $channels = config('finance.billing.notification_channels', ['email']);

        foreach ($channels as $channel) {
            try {
                match ($channel) {
                    'email' => $this->sendEmail($invoice),
                    'sms' => $this->sendSms($invoice),
                    default => Log::warning("Canal de notificacion desconocido: {$channel}"),
                };
            } catch (\Throwable $e) {
                Log::error("Error enviando notificacion de factura #{$invoice->invoice_number} por {$channel}: {$e->getMessage()}");
            }
        }
    }

    protected function sendEmail($invoice): void
    {
        Log::info("Factura #{$invoice->invoice_number} - notificacion email pendiente de implementar para customer #{$invoice->customer_id}");
        // TODO: Implementar envio de email con la factura
    }

    protected function sendSms($invoice): void
    {
        Log::info("Factura #{$invoice->invoice_number} - notificacion SMS pendiente de implementar para customer #{$invoice->customer_id}");
        // TODO: Implementar envio de SMS
    }
}
