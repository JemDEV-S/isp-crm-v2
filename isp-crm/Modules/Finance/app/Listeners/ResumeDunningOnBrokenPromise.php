<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Events\PromiseToPayBroken;

class ResumeDunningOnBrokenPromise
{
    public function handle(PromiseToPayBroken $event): void
    {
        $promise = $event->promise;

        if ($promise->invoice_id) {
            Invoice::where('id', $promise->invoice_id)->update([
                'dunning_paused' => false,
                'dunning_pause_reason' => null,
            ]);
        } else {
            Invoice::where('subscription_id', $promise->subscription_id)
                ->where('dunning_pause_reason', 'promise_active')
                ->whereNotIn('status', [InvoiceStatus::PAID->value, InvoiceStatus::CANCELLED->value])
                ->update([
                    'dunning_paused' => false,
                    'dunning_pause_reason' => null,
                ]);
        }
    }
}
