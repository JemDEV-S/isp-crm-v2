<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\FieldOps\app\Models\WorkOrder;

class InstallationScheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WorkOrder $workOrder,
        public readonly array $metadata = [],
    ) {}
}
