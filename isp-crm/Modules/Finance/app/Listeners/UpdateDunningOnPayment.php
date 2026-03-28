<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Entities\DunningExecution;
use Modules\Finance\Events\InvoicePaid;

class UpdateDunningOnPayment
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        // Pausar/cerrar dunning de la factura pagada
        if ($invoice->dunning_paused === false) {
            $invoice->update([
                'dunning_paused' => true,
                'dunning_pause_reason' => 'Factura pagada',
            ]);
        }

        // Marcar ejecuciones de dunning pendientes como resueltas
        DunningExecution::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'resolved',
                'result' => 'Factura pagada completamente',
            ]);

        Log::info("Dunning pausado para factura #{$invoice->id} por pago completo");
    }
}
