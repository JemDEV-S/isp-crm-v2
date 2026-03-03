<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Services;

use Modules\FieldOps\app\Models\ChecklistResponse;
use Modules\FieldOps\app\Models\WorkOrder;

class ChecklistService
{
    public function submitResponse(WorkOrder $workOrder, array $responses, int $completedBy): ChecklistResponse
    {
        $checklistTemplateId = $workOrder->workOrderType->checklist_template_id;

        if (!$checklistTemplateId) {
            throw new \RuntimeException('Este tipo de orden de trabajo no tiene checklist asociado');
        }

        return ChecklistResponse::updateOrCreate(
            ['work_order_id' => $workOrder->id],
            [
                'checklist_template_id' => $checklistTemplateId,
                'responses' => $responses,
                'completed_at' => now(),
                'completed_by' => $completedBy,
            ]
        );
    }

    public function validateResponse(array $responses, int $checklistTemplateId): bool
    {
        // Aquí se puede agregar lógica de validación personalizada
        // Por ejemplo, verificar que todos los campos requeridos estén completos
        return true;
    }
}
