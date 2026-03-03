<?php

declare(strict_types=1);

namespace Modules\Workflow\Contracts;

use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\Transition;

interface SideEffectActionInterface
{
    public function execute(Token $token, Transition $transition, array $parameters = []): void;
}
