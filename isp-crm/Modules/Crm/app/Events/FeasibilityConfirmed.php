<?php

declare(strict_types=1);

namespace Modules\Crm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Crm\Entities\FeasibilityRequest;

class FeasibilityConfirmed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public FeasibilityRequest $request,
    ) {}
}
