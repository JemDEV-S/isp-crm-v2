<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;

class CoreException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception.
     */
    public function report(): bool
    {
        return true;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => true,
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
            ], 400);
        }

        return redirect()->back()->with('error', $this->getMessage());
    }
}
