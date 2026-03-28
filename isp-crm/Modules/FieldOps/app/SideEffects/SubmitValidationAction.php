<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\SideEffects;

use Modules\FieldOps\app\Models\WorkOrder;
use Modules\FieldOps\app\Services\ValidationService;
use Modules\Workflow\Contracts\SideEffectActionInterface;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\Transition;

class SubmitValidationAction implements SideEffectActionInterface
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

        $result = $this->validationService->validate($workOrder->id, $parameters);

        if (!$result->passed) {
            throw new \DomainException(
                'La orden no esta lista para validacion: ' . implode(', ', $result->issues),
            );
        }
    }
}
