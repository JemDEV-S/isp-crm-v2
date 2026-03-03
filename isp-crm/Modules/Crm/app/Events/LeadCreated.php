<?php

declare(strict_types=1);

namespace Modules\Crm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Crm\Entities\Lead;

class LeadCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead
    ) {}
}
