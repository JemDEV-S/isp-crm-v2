<?php

declare(strict_types=1);

namespace Modules\Inventory\Listeners;

use Modules\FieldOps\app\Events\InstallationValidated;
use Modules\FieldOps\app\Models\MaterialUsage;
use Modules\Inventory\Entities\Movement;
use Modules\Inventory\Enums\MovementType;
use Modules\Inventory\Services\MovementService;

class ConfirmMaterialConsumption
{
    public function __construct(
        protected MovementService $movementService,
    ) {}

    public function handle(InstallationValidated $event): void
    {
        $event->workOrder->loadMissing('materialUsages');

        foreach ($event->workOrder->materialUsages as $usage) {
            if ($this->movementAlreadyRegistered($usage->id)) {
                continue;
            }

            $this->movementService->createInstallation(
                productId: $usage->product_id,
                warehouseId: $usage->warehouse_id,
                quantity: (float) $usage->quantity,
                serialId: $usage->serial_id,
                referenceType: MaterialUsage::class,
                referenceId: $usage->id,
                notes: $usage->notes ?: 'Consumo confirmado por instalacion validada',
            );
        }
    }

    protected function movementAlreadyRegistered(int $materialUsageId): bool
    {
        return Movement::query()
            ->where('type', MovementType::INSTALLATION)
            ->where('reference_type', MaterialUsage::class)
            ->where('reference_id', $materialUsageId)
            ->exists();
    }
}
