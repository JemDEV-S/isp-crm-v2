<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Inventory\Entities\Movement;
use Modules\Inventory\Entities\Serial;
use Modules\Inventory\DTOs\CreateMovementDTO;
use Modules\Inventory\Enums\MovementType;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Events\MovementCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MovementService
{
    public function __construct(
        private StockService $stockService
    ) {}

    /**
     * Crear un movimiento de inventario
     */
    public function createMovement(CreateMovementDTO $dto): Movement
    {
        // Validar DTO
        $errors = $dto->validate();
        if (!empty($errors)) {
            throw new \InvalidArgumentException(json_encode($errors));
        }

        return DB::transaction(function () use ($dto) {
            // Crear el movimiento
            $movement = Movement::create([
                'uuid' => Str::uuid(),
                'type' => $dto->type,
                'product_id' => $dto->productId,
                'quantity' => $dto->quantity,
                'from_warehouse_id' => $dto->fromWarehouseId,
                'to_warehouse_id' => $dto->toWarehouseId,
                'serial_id' => $dto->serialId,
                'reference_type' => $dto->referenceType,
                'reference_id' => $dto->referenceId,
                'unit_cost' => $dto->unitCost,
                'notes' => $dto->notes,
                'user_id' => $dto->userId ?? auth()->id(),
            ]);

            // Aplicar el movimiento al stock
            $this->applyMovement($movement);

            // Si es un serial, actualizar su estado
            if ($movement->serial_id) {
                $this->updateSerialStatus($movement);
            }

            event(new MovementCreated($movement));

            return $movement;
        });
    }

    /**
     * Aplicar movimiento al stock
     */
    private function applyMovement(Movement $movement): void
    {
        match($movement->type) {
            MovementType::PURCHASE,
            MovementType::ADJUSTMENT_IN,
            MovementType::RETURN => $this->stockService->addStock(
                $movement->product_id,
                $movement->to_warehouse_id,
                $movement->quantity
            ),

            MovementType::SALE,
            MovementType::ADJUSTMENT_OUT,
            MovementType::INSTALLATION,
            MovementType::DAMAGE => $this->stockService->subtractStock(
                $movement->product_id,
                $movement->from_warehouse_id,
                $movement->quantity
            ),

            MovementType::TRANSFER => $this->stockService->transferStock(
                $movement->product_id,
                $movement->from_warehouse_id,
                $movement->to_warehouse_id,
                $movement->quantity
            ),
        };
    }

    /**
     * Actualizar estado del serial según tipo de movimiento
     */
    private function updateSerialStatus(Movement $movement): void
    {
        $serial = Serial::find($movement->serial_id);
        if (!$serial) return;

        $newStatus = match($movement->type) {
            MovementType::PURCHASE,
            MovementType::RETURN => SerialStatus::IN_STOCK,

            MovementType::TRANSFER => SerialStatus::IN_TRANSIT,

            MovementType::INSTALLATION => SerialStatus::ASSIGNED,

            MovementType::DAMAGE => SerialStatus::DAMAGED,

            default => $serial->status,
        };

        $serial->update([
            'status' => $newStatus,
            'warehouse_id' => $movement->to_warehouse_id ?? $movement->from_warehouse_id,
        ]);
    }

    /**
     * Crear movimiento de compra
     */
    public function createPurchase(
        int $productId,
        int $warehouseId,
        float $quantity,
        float $unitCost,
        ?string $notes = null
    ): Movement {
        $dto = new CreateMovementDTO(
            type: MovementType::PURCHASE,
            productId: $productId,
            quantity: $quantity,
            toWarehouseId: $warehouseId,
            unitCost: $unitCost,
            notes: $notes
        );

        return $this->createMovement($dto);
    }

    /**
     * Crear movimiento de transferencia
     */
    public function createTransfer(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        ?string $notes = null
    ): Movement {
        $dto = new CreateMovementDTO(
            type: MovementType::TRANSFER,
            productId: $productId,
            quantity: $quantity,
            fromWarehouseId: $fromWarehouseId,
            toWarehouseId: $toWarehouseId,
            notes: $notes
        );

        return $this->createMovement($dto);
    }

    /**
     * Crear movimiento de instalación
     */
    public function createInstallation(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?int $serialId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): Movement {
        $dto = new CreateMovementDTO(
            type: MovementType::INSTALLATION,
            productId: $productId,
            quantity: $quantity,
            fromWarehouseId: $warehouseId,
            serialId: $serialId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            notes: $notes
        );

        return $this->createMovement($dto);
    }

    /**
     * Crear movimiento de ajuste
     */
    public function createAdjustment(
        int $productId,
        int $warehouseId,
        float $quantity,
        bool $isPositive = true,
        ?string $notes = null
    ): Movement {
        $dto = new CreateMovementDTO(
            type: $isPositive ? MovementType::ADJUSTMENT_IN : MovementType::ADJUSTMENT_OUT,
            productId: $productId,
            quantity: abs($quantity),
            toWarehouseId: $isPositive ? $warehouseId : null,
            fromWarehouseId: $isPositive ? null : $warehouseId,
            notes: $notes
        );

        return $this->createMovement($dto);
    }

    /**
     * Obtener historial de movimientos
     */
    public function getMovementHistory(
        ?int $productId = null,
        ?int $warehouseId = null,
        ?MovementType $type = null,
        int $limit = 50
    ) {
        $query = Movement::with(['product', 'fromWarehouse', 'toWarehouse', 'user'])
            ->latest();

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        if ($type) {
            $query->ofType($type);
        }

        return $query->paginate($limit);
    }
}
