<?php

declare(strict_types=1);

namespace Modules\Subscription\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Subscription\Entities\PlanChangeRequest;

class PlanChangeBilled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PlanChangeRequest $planChangeRequest,
        public readonly mixed $financialRecord, // Invoice o WalletTransaction
    ) {}
}
