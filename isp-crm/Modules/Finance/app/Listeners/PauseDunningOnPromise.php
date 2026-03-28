<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Events\PromiseToPayCreated;

class PauseDunningOnPromise
{
    public function handle(PromiseToPayCreated $event): void
    {
        $promise = $event->promise;

        // Pausar dunning en facturas vencidas de la suscripción
        if ($promise->invoice_id) {
            Invoice::where('id', $promise->invoice_id)->update([
                'dunning_paused' => true,
                'dunning_pause_reason' => 'promise_active',
            ]);
        } else {
            Invoice::where('subscription_id', $promise->subscription_id)
                ->where('due_date', '<', now())
                ->whereNotIn('status', [InvoiceStatus::PAID->value, InvoiceStatus::CANCELLED->value])
                ->update([
                    'dunning_paused' => true,
                    'dunning_pause_reason' => 'promise_active',
                ]);
        }
    }
}
