<?php

declare(strict_types=1);

namespace Modules\Workflow\Exceptions;

class UnauthorizedTransitionException extends WorkflowException
{
    public function __construct(string $transitionCode)
    {
        parent::__construct("No tienes permiso para ejecutar la transición: {$transitionCode}");
    }
}
