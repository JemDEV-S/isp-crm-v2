<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\DTOs;

use Carbon\Carbon;

final readonly class AssignWorkOrderDTO
{
    public function __construct(
        public int $technicianId,
        public ?Carbon $scheduledDate = null,
        public ?string $scheduledTimeSlot = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            technicianId: $data['technician_id'],
            scheduledDate: isset($data['scheduled_date']) ? Carbon::parse($data['scheduled_date']) : null,
            scheduledTimeSlot: $data['scheduled_time_slot'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
