<?php

declare(strict_types=1);

namespace Modules\Crm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Crm\Entities\CapacityReservation;

class CapacityReserved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CapacityReservation $reservation,
    ) {}
}
