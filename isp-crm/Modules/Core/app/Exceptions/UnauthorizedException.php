<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class UnauthorizedException extends CoreException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'No tienes permisos para realizar esta acción.')
    {
        parent::__construct($message, Response::HTTP_FORBIDDEN);
    }
}
