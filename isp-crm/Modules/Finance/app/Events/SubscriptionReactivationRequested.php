<?php

declare(strict_types=1);

namespace Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Entities\Invoice;
use Modules\Subscription\Entities\Subscription;

class SubscriptionReactivationRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly Invoice $invoice,
    ) {}
}
