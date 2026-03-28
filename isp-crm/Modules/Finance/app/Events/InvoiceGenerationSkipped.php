<?php

declare(strict_types=1);

namespace Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Entities\BillingIncident;
use Modules\Subscription\Entities\Subscription;

class InvoiceGenerationSkipped
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly string $reason,
        public readonly BillingIncident $incident,
    ) {}
}
