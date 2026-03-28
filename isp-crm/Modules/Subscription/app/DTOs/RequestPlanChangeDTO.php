<?php

declare(strict_types=1);

namespace Modules\Subscription\DTOs;

use Carbon\Carbon;

final readonly class RequestPlanChangeDTO
{
    public function __construct(
        public int $subscriptionId,
        public int $newPlanId,
        public string $effectiveMode = 'immediate',
        public ?Carbon $scheduledFor = null,
        public ?string $notes = null,
        public ?int $requestedBy = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionId: $data['subscription_id'],
            newPlanId: $data['new_plan_id'],
            effectiveMode: $data['effective_mode'] ?? 'immediate',
            scheduledFor: isset($data['scheduled_for']) ? Carbon::parse($data['scheduled_for']) : null,
            notes: $data['notes'] ?? null,
            requestedBy: $data['requested_by'] ?? (auth()->check() ? auth()->id() : null),
        );
    }
}
