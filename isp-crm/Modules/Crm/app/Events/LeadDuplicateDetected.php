<?php

declare(strict_types=1);

namespace Modules\Crm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Crm\Entities\Lead;

class LeadDuplicateDetected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Lead $lead,
        public Lead $matchedLead,
        public array $matchedBy = [],
    ) {}
}
