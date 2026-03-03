<?php

declare(strict_types=1);

namespace Modules\Core\DTOs;

final readonly class ProvisionResult
{
    public function __construct(
        public bool $success,
        public ?string $message = null,
        public ?array $data = null,
        public ?string $errorCode = null,
    ) {}

    /**
     * Create a successful provision result.
     */
    public static function success(?string $message = null, ?array $data = null): self
    {
        return new self(
            success: true,
            message: $message ?? 'Provision successful',
            data: $data
        );
    }

    /**
     * Create a failed provision result.
     */
    public static function failed(string $message, ?string $errorCode = null, ?array $data = null): self
    {
        return new self(
            success: false,
            message: $message,
            data: $data,
            errorCode: $errorCode
        );
    }

    /**
     * Check if the provision was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the provision failed.
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Get a specific data value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'error_code' => $this->errorCode,
        ];
    }
}
