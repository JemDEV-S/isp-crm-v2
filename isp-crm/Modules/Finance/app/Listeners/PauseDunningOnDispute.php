<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Modules\Finance\Entities\Invoice;
use Modules\Finance\Events\InvoiceDisputeOpened;

class PauseDunningOnDispute
{
    public function handle(InvoiceDisputeOpened $event): void
    {
        Invoice::where('id', $event->dispute->invoice_id)->update([
            'dunning_paused' => true,
            'dunning_pause_reason' => 'dispute_open',
        ]);
    }
}
