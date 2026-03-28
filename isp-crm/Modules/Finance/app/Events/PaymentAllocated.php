<?php

declare(strict_types=1);

namespace Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Entities\PaymentAllocation;

class PaymentAllocated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly PaymentAllocation $allocation,
        public readonly Invoice $invoice,
    ) {}
}
