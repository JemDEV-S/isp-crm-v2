<?php

declare(strict_types=1);

namespace Modules\AccessControl\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AccessControl\Entities\Zone;

class ZoneCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Zone $zone
    ) {
    }
}
