<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\SideEffects;

use Modules\FieldOps\app\Models\WorkOrder;
use Modules\Inventory\Services\MovementRequestService;
use Modules\Workflow\Contracts\SideEffectActionInterface;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\Transition;

class ReserveMaterialsAction implements SideEffectActionInterface
{
    public function __construct(
        private readonly MovementRequestService $movementRequestService,
    ) {}

    public function execute(Token $token, Transition $transition, array $parameters = []): void
    {
        $workOrder = $token->tokenable;

        if (!$workOrder instanceof WorkOrder) {
            return;
        }

        $items = $parameters['items'] ?? $token->getContext('materials', []);
        $fromWarehouseId = $parameters['from_warehouse_id'] ?? $token->getContext('warehouse_id');

        if (empty($items) || empty($fromWarehouseId)) {
            return;
        }

        $this->movementRequestService->createRequest(
            'transfer',
            (int) $fromWarehouseId,
            $parameters['to_warehouse_id'] ?? null,
            $items,
            'Reserva automatica para orden ' . $workOrder->code
        );
    }
}
