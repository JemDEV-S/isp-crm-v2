<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ValidationException extends CoreException
{
    /**
     * Validation errors.
     */
    protected array $errors = [];

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Los datos proporcionados no son válidos.', array $errors = [])
    {
        parent::__construct($message, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
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
                'errors' => $this->errors,
            ], $this->getCode());
        }

        return redirect()->back()
            ->withInput()
            ->withErrors($this->errors)
            ->with('error', $this->getMessage());
    }
}
