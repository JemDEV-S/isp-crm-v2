<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Listeners;

use Modules\FieldOps\app\Events\InstallationMaterialsReserved;
use Modules\FieldOps\app\Events\InstallationRejected;
use Modules\FieldOps\app\Events\InstallationScheduled;
use Modules\FieldOps\app\Events\InstallationSubmittedForValidation;
use Modules\FieldOps\app\Events\InstallationWorkflowStarted;
use Modules\FieldOps\app\Events\TechnicianArrived;
use Modules\FieldOps\app\Events\TechnicianDispatched;
use Modules\FieldOps\app\Models\WorkOrder;
use Modules\Workflow\Events\TransitionExecuted;
use Modules\Workflow\Events\WorkflowStarted;

class EmitInstallationDomainEvents
{
    public function handleWorkflowStarted(WorkflowStarted $event): void
    {
        $workOrder = $event->token->tokenable;

        if (!$this->isInstallationWorkOrder($workOrder, $event->workflow->code)) {
            return;
        }

        event(new InstallationWorkflowStarted($workOrder, $event->token->context ?? []));
    }

    public function handleTransitionExecuted(TransitionExecuted $event): void
    {
        $workOrder = $event->token->tokenable;

        if (!$this->isInstallationWorkOrder($workOrder, $event->token->workflow->code ?? null)) {
            return;
        }

        match ($event->transition->code) {
            'schedule' => event(new InstallationScheduled($workOrder, $event->metadata)),
            'reserve_materials' => event(new InstallationMaterialsReserved($workOrder, $event->metadata)),
            'start_transit' => event(new TechnicianDispatched($workOrder, $event->metadata)),
            'arrive' => event(new TechnicianArrived($workOrder, $event->metadata)),
            'submit_validation' => event(new InstallationSubmittedForValidation($workOrder, $event->metadata)),
            'reject' => event(new InstallationRejected($workOrder, $event->metadata)),
            default => null,
        };
    }

    protected function isInstallationWorkOrder(mixed $tokenable, ?string $workflowCode): bool
    {
        return $tokenable instanceof WorkOrder && $workflowCode === 'installation';
    }
}
