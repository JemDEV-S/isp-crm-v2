<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\SideEffects;

use Modules\FieldOps\app\Models\WorkOrder;
use Modules\FieldOps\app\Services\GeofenceService;
use Modules\Workflow\Contracts\SideEffectActionInterface;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\Transition;

class ValidateGeofenceAction implements SideEffectActionInterface
{
    public function __construct(
        private readonly GeofenceService $geofenceService,
    ) {}

    public function execute(Token $token, Transition $transition, array $parameters = []): void
    {
        $workOrder = $token->tokenable;

        if (!$workOrder instanceof WorkOrder) {
            return;
        }

        $location = $workOrder->technicianLocations()->latest('recorded_at')->first();

        if (!$location) {
            throw new \DomainException('No existe ubicacion registrada para validar la llegada');
        }

        $result = $this->geofenceService->validateWorkOrderArrival(
            $workOrder,
            $location,
            (int) ($parameters['radius_meters'] ?? 100),
        );

        if (!$result['valid']) {
            throw new \DomainException($result['reason'] ?? 'La validacion de geofence fallo');
        }
    }
}
