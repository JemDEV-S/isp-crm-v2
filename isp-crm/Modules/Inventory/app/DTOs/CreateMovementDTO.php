<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

use Modules\Inventory\Enums\MovementType;

final readonly class CreateMovementDTO
{
    public function __construct(
        public MovementType $type,
        public int $productId,
        public float $quantity,
        public ?int $fromWarehouseId = null,
        public ?int $toWarehouseId = null,
        public ?int $serialId = null,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public float $unitCost = 0,
        public ?string $notes = null,
        public ?int $userId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: MovementType::from($data['type']),
            productId: $data['product_id'],
            quantity: (float) $data['quantity'],
            fromWarehouseId: $data['from_warehouse_id'] ?? null,
            toWarehouseId: $data['to_warehouse_id'] ?? null,
            serialId: $data['serial_id'] ?? null,
            referenceType: $data['reference_type'] ?? null,
            referenceId: $data['reference_id'] ?? null,
            unitCost: (float) ($data['unit_cost'] ?? 0),
            notes: $data['notes'] ?? null,
            userId: $data['user_id'] ?? auth()->id(),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'from_warehouse_id' => $this->fromWarehouseId,
            'to_warehouse_id' => $this->toWarehouseId,
            'serial_id' => $this->serialId,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'unit_cost' => $this->unitCost,
            'notes' => $this->notes,
            'user_id' => $this->userId,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->quantity <= 0) {
            $errors['quantity'] = 'La cantidad debe ser mayor a 0';
        }

        if ($this->type === MovementType::TRANSFER) {
            if (!$this->fromWarehouseId || !$this->toWarehouseId) {
                $errors['warehouses'] = 'Las transferencias requieren almacén origen y destino';
            }
            if ($this->fromWarehouseId === $this->toWarehouseId) {
                $errors['warehouses'] = 'El almacén origen y destino no pueden ser el mismo';
            }
        }

        if ($this->type->isIncoming() && !$this->toWarehouseId) {
            $errors['to_warehouse_id'] = 'Se requiere almacén de destino';
        }

        if ($this->type->isOutgoing() && !$this->fromWarehouseId) {
            $errors['from_warehouse_id'] = 'Se requiere almacén de origen';
        }

        return $errors;
    }
}
