<?php

declare(strict_types=1);

namespace Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\WorkflowDefinition;

class WorkflowCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Token $token,
        public readonly WorkflowDefinition $workflow
    ) {}
}
