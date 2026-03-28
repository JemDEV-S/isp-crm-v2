<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

use Carbon\Carbon;

final readonly class RegisterPaymentDTO
{
    public function __construct(
        public int $customerId,
        public float $amount,
        public string $method,
        public string $channel,
        public ?int $invoiceId = null,
        public ?string $reference = null,
        public ?string $externalId = null,
        public ?string $idempotencyKey = null,
        public ?array $gatewayResponse = null,
        public ?Carbon $receivedAt = null,
        public ?int $processedBy = null,
        public ?string $notes = null,
    ) {}
}
