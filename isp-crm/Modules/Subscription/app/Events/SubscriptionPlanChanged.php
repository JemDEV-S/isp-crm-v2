<?php

declare(strict_types=1);

namespace Modules\Subscription\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Subscription\Entities\Subscription;

class SubscriptionPlanChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly array $oldPlanSnapshot,
        public readonly array $newPlanSnapshot,
    ) {}
}
