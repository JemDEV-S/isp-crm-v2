<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\FieldOps\app\DTOs\AssignWorkOrderDTO;
use Modules\FieldOps\app\DTOs\CompleteWorkOrderDTO;
use Modules\FieldOps\app\DTOs\CreateWorkOrderDTO;
use Modules\FieldOps\app\Models\WorkOrder;
use Modules\FieldOps\app\Models\WorkOrderType;
use Modules\FieldOps\app\Events\WorkOrderCreated;
use Modules\FieldOps\app\Events\WorkOrderAssigned;
use Modules\FieldOps\app\Events\WorkOrderStarted;
use Modules\FieldOps\app\Events\WorkOrderCompleted;

class WorkOrderService
{
    public function __construct(
        private readonly ChecklistService $checklistService,
        private readonly MaterialUsageService $materialUsageService,
    ) {}

    public function create(CreateWorkOrderDTO $dto): WorkOrder
    {
        return DB::transaction(function () use ($dto) {
            $workOrderType = WorkOrderType::where('code', $dto->type->value)->firstOrFail();

            $workOrder = WorkOrder::create([
                'work_order_type_id' => $workOrderType->id,
                'type' => $dto->type,
                'customer_id' => $dto->customerId,
                'address_id' => $dto->addressId,
                'subscription_id' => $dto->subscriptionId,
                'priority' => $dto->priority,
                'assigned_to' => $dto->assignedTo,
                'scheduled_date' => $dto->scheduledDate,
                'scheduled_time_slot' => $dto->scheduledTimeSlot,
                'notes' => $dto->notes,
                'created_by' => $dto->createdBy,
            ]);

            // Iniciar workflow si el tipo de orden lo tiene configurado
            if ($workOrderType->workflow_code) {
                $workOrder->startWorkflow($workOrderType->workflow_code);
            }

            event(new WorkOrderCreated($workOrder));

            return $workOrder->fresh();
        });
    }

    public function assign(WorkOrder $workOrder, AssignWorkOrderDTO $dto): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $dto) {
            $workOrder->update([
                'assigned_to' => $dto->technicianId,
                'scheduled_date' => $dto->scheduledDate ?? $workOrder->scheduled_date,
                'scheduled_time_slot' => $dto->scheduledTimeSlot ?? $workOrder->scheduled_time_slot,
                'notes' => $dto->notes ? ($workOrder->notes . "\n" . $dto->notes) : $workOrder->notes,
            ]);

            // Ejecutar transición de workflow si está disponible
            if ($workOrder->canTransition('assign')) {
                $workOrder->executeTransition('assign', [
                    'technician_id' => $dto->technicianId,
                    'scheduled_date' => $dto->scheduledDate?->toDateString(),
                ]);
            }

            event(new WorkOrderAssigned($workOrder, $dto->technicianId));

            return $workOrder->fresh();
        });
    }

    public function start(WorkOrder $workOrder, array $metadata = []): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $metadata) {
            $workOrder->update([
                'started_at' => now(),
            ]);

            // Ejecutar transición de workflow
            if ($workOrder->canTransition('start_work')) {
                $workOrder->executeTransition('start_work', $metadata);
            }

            event(new WorkOrderStarted($workOrder));

            return $workOrder->fresh();
        });
    }

    public function complete(WorkOrder $workOrder, CompleteWorkOrderDTO $dto): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $dto) {
            // Guardar checklist
            if (!empty($dto->checklistResponses)) {
                $this->checklistService->submitResponse(
                    $workOrder,
                    $dto->checklistResponses,
                    $dto->completedBy
                );
            }

            // Registrar materiales utilizados
            if (!empty($dto->materials)) {
                foreach ($dto->materials as $material) {
                    $this->materialUsageService->record(
                        $workOrder,
                        $material['product_id'],
                        $material['quantity'],
                        $material['warehouse_id'],
                        $material['serial_id'] ?? null,
                        $material['notes'] ?? null,
                        $dto->completedBy
                    );
                }
            }

            // Actualizar orden
            $workOrder->update([
                'completed_at' => now(),
                'notes' => $dto->notes ? ($workOrder->notes . "\n" . $dto->notes) : $workOrder->notes,
            ]);

            // Ejecutar transición de workflow
            if ($workOrder->canTransition('submit_completion')) {
                $workOrder->executeTransition('submit_completion', [
                    'completed_by' => $dto->completedBy,
                    'materials_count' => count($dto->materials),
                    'photos_count' => count($dto->photos),
                ]);
            }

            event(new WorkOrderCompleted($workOrder));

            return $workOrder->fresh();
        });
    }

    public function cancel(WorkOrder $workOrder, string $reason, int $cancelledBy): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $reason, $cancelledBy) {
            // Ejecutar transición de workflow
            if ($workOrder->canTransition('cancel')) {
                $workOrder->executeTransition('cancel', [
                    'reason' => $reason,
                    'cancelled_by' => $cancelledBy,
                ]);
            }

            return $workOrder->fresh();
        });
    }

    public function reschedule(WorkOrder $workOrder, $newDate, $newTimeSlot, string $reason): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $newDate, $newTimeSlot, $reason) {
            $workOrder->update([
                'scheduled_date' => $newDate,
                'scheduled_time_slot' => $newTimeSlot,
                'notes' => $workOrder->notes . "\nReagendado: " . $reason,
            ]);

            // Ejecutar transición de workflow si está disponible
            if ($workOrder->canTransition('reschedule')) {
                $workOrder->executeTransition('reschedule', [
                    'new_date' => $newDate,
                    'new_time_slot' => $newTimeSlot,
                    'reason' => $reason,
                ]);
            }

            return $workOrder->fresh();
        });
    }
}
