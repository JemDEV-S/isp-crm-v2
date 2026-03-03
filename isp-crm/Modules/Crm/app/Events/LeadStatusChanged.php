<?php

declare(strict_types=1);

namespace Modules\Crm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadStatus;

class LeadStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly LeadStatus $oldStatus,
        public readonly LeadStatus $newStatus
    ) {}
}
