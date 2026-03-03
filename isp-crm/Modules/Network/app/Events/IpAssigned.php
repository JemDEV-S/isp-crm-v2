<?php

declare(strict_types=1);

namespace Modules\Network\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Network\Entities\IpAddress;

class IpAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly IpAddress $ipAddress,
        public readonly int $subscriptionId,
    ) {}
}
