<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends CoreException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'El recurso solicitado no fue encontrado.')
    {
        parent::__construct($message, Response::HTTP_NOT_FOUND);
    }
}
