<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\DisputeStatus;
use Modules\Finance\Events\InvoiceDisputeResolved;

class ResumeDunningOnDisputeResolved
{
    public function handle(InvoiceDisputeResolved $event): void
    {
        $dispute = $event->dispute;

        // Si se resolvió a favor del cliente, no reanudar dunning
        if ($dispute->status === DisputeStatus::RESOLVED_FAVOR_CUSTOMER) {
            return;
        }

        // Verificar que no haya otras disputas abiertas para la misma factura
        $hasOtherOpenDisputes = $dispute->invoice->disputes()
            ->where('id', '!=', $dispute->id)
            ->whereIn('status', [DisputeStatus::OPEN->value, DisputeStatus::UNDER_REVIEW->value])
            ->exists();

        if ($hasOtherOpenDisputes) {
            return;
        }

        Invoice::where('id', $dispute->invoice_id)
            ->where('dunning_pause_reason', 'dispute_open')
            ->update([
                'dunning_paused' => false,
                'dunning_pause_reason' => null,
            ]);
    }
}
