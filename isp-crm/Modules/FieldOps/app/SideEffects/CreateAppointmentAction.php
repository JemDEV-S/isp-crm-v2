<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\SideEffects;

use Modules\FieldOps\app\Models\Appointment;
use Modules\FieldOps\app\Models\WorkOrder;
use Modules\Workflow\Contracts\SideEffectActionInterface;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\Transition;

class CreateAppointmentAction implements SideEffectActionInterface
{
    public function execute(Token $token, Transition $transition, array $parameters = []): void
    {
        $workOrder = $token->tokenable;

        if (!$workOrder instanceof WorkOrder || !$workOrder->scheduled_date) {
            return;
        }

        [$start, $end] = $this->resolveTimeSlot((string) $workOrder->scheduled_time_slot);

        Appointment::updateOrCreate(
            ['work_order_id' => $workOrder->id],
            [
                'date' => $workOrder->scheduled_date,
                'time_slot_start' => $start,
                'time_slot_end' => $end,
                'notes' => $workOrder->notes,
            ]
        );
    }

    protected function resolveTimeSlot(string $slot): array
    {
        return match ($slot) {
            'afternoon' => ['13:00:00', '17:00:00'],
            'evening' => ['17:00:00', '20:00:00'],
            default => ['08:00:00', '12:00:00'],
        };
    }
}
