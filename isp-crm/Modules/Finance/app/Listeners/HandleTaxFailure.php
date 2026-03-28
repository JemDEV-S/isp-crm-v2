<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\InvoiceTaxSubmissionFailed;

class HandleTaxFailure
{
    public function handle(InvoiceTaxSubmissionFailed $event): void
    {
        $invoice = $event->invoice;

        $invoice->update([
            'external_tax_status' => 'rejected',
        ]);

        Log::error("Fallo en integracion fiscal para factura #{$invoice->invoice_number}: {$event->reason}");

        // TODO: Crear incidencia y alerta administrativa
    }
}
