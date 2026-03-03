<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\DTOs;

use Carbon\Carbon;
use Modules\FieldOps\app\Enums\WorkOrderPriority;
use Modules\FieldOps\app\Enums\WorkOrderType;

final readonly class CreateWorkOrderDTO
{
    public function __construct(
        public WorkOrderType $type,
        public int $customerId,
        public int $addressId,
        public ?int $subscriptionId = null,
        public WorkOrderPriority $priority = WorkOrderPriority::NORMAL,
        public ?int $assignedTo = null,
        public ?Carbon $scheduledDate = null,
        public ?string $scheduledTimeSlot = null,
        public ?string $notes = null,
        public int $createdBy = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: WorkOrderType::from($data['type']),
            customerId: $data['customer_id'],
            addressId: $data['address_id'],
            subscriptionId: $data['subscription_id'] ?? null,
            priority: isset($data['priority']) ? WorkOrderPriority::from($data['priority']) : WorkOrderPriority::NORMAL,
            assignedTo: $data['assigned_to'] ?? null,
            scheduledDate: isset($data['scheduled_date']) ? Carbon::parse($data['scheduled_date']) : null,
            scheduledTimeSlot: $data['scheduled_time_slot'] ?? null,
            notes: $data['notes'] ?? null,
            createdBy: $data['created_by'] ?? auth()->id(),
        );
    }
}
