<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\SideEffects;

use Modules\FieldOps\app\Events\InstallationValidated;
use Modules\FieldOps\app\Models\WorkOrder;
use Modules\FieldOps\app\Services\ValidationService;
use Modules\Workflow\Contracts\SideEffectActionInterface;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\Transition;

class CompleteInstallationAction implements SideEffectActionInterface
{
    public function __construct(
        private readonly ValidationService $validationService,
    ) {}

    public function execute(Token $token, Transition $transition, array $parameters = []): void
    {
        $workOrder = $token->tokenable;

        if (!$workOrder instanceof WorkOrder) {
            return;
        }

        $result = $this->validationService->approve($workOrder->id, $parameters, auth()->id());

        if (!$result->passed) {
            throw new \DomainException(
                'La orden no cumple criterios de aprobacion: ' . implode(', ', $result->issues),
            );
        }

        event(new InstallationValidated($workOrder->fresh(['subscription', 'materialUsages']), $result->toArray()));
    }
}
