<?php

declare(strict_types=1);

namespace Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\Transition;
use Modules\Workflow\Entities\Place;

class TransitionExecuted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Token $token,
        public readonly Transition $transition,
        public readonly Place $fromPlace,
        public readonly Place $toPlace,
        public readonly array $metadata = []
    ) {}
}
