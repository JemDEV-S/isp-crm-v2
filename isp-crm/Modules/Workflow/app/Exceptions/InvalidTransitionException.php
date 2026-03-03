<?php

declare(strict_types=1);

namespace Modules\Workflow\Exceptions;

class InvalidTransitionException extends WorkflowException
{
    public function __construct(string $transitionCode, string $currentPlace)
    {
        parent::__construct("La transición '{$transitionCode}' no es válida desde el estado '{$currentPlace}'");
    }
}
