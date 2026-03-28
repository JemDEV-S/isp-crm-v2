<?php

declare(strict_types=1);

namespace Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Entities\InvoiceDispute;

class InvoiceDisputeOpened
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly InvoiceDispute $dispute,
    ) {}
}
