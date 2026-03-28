<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\InvoiceGenerated;

class SchedulePaymentReminders
{
    public function handle(InvoiceGenerated $event): void
    {
        $invoice = $event->invoice;

        Log::info("Recordatorios de pago programados para factura #{$invoice->invoice_number}, vencimiento: {$invoice->due_date->toDateString()}");
        // TODO: Implementar programacion de recordatorios antes del vencimiento
    }
}
