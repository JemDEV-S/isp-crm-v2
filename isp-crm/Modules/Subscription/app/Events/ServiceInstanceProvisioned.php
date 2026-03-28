<?php

declare(strict_types=1);

namespace Modules\Subscription\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Subscription\Entities\ServiceInstance;
use Modules\Subscription\Entities\Subscription;

class ServiceInstanceProvisioned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly ServiceInstance $serviceInstance,
        public readonly array $provisionData = [],
    ) {}
}
