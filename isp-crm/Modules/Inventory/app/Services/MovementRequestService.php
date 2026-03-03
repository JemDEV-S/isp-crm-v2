<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Inventory\Entities\MovementRequest;
use Modules\Inventory\Entities\MovementRequestItem;
use Modules\Inventory\DTOs\CreateMovementDTO;
use Modules\Inventory\Enums\MovementType;
use Modules\Inventory\Events\MovementRequestCreated;
use Modules\Inventory\Events\MovementRequestApproved;
use Modules\Inventory\Events\MovementRequestRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MovementRequestService
{
    public function __construct(
        private StockService $stockService,
        private MovementService $movementService
    ) {}

    /**
     * Crear solicitud de transferencia
     */
    public function createRequest(
        string $type,
        int $fromWarehouseId,
        ?int $toWarehouseId,
        array $items,
        ?string $notes = null
    ): MovementRequest {
        return DB::transaction(function () use ($type, $fromWarehouseId, $toWarehouseId, $items, $notes) {
            // Crear la solicitud
            $request = MovementRequest::create([
                'uuid' => Str::uuid(),
                'type' => $type,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'status' => 'pending',
                'requested_by' => auth()->id(),
                'notes' => $notes,
            ]);

            // Crear los items
            foreach ($items as $item) {
                MovementRequestItem::create([
                    'request_id' => $request->id,
                    'product_id' => $item['product_id'],
                    'quantity_requested' => $item['quantity'],
                    'serial_id' => $item['serial_id'] ?? null,
                ]);
            }

            // Reservar stock si es necesario
            if ($type === 'transfer') {
                foreach ($request->items as $item) {
                    $this->stockService->reserveStock(
                        $item->product_id,
                        $fromWarehouseId,
                        $item->quantity_requested
                    );
                }
            }

            event(new MovementRequestCreated($request));

            return $request->load('items.product');
        });
    }

    /**
     * Aprobar solicitud
     */
    public function approveRequest(
        int $requestId,
        ?array $approvedQuantities = null
    ): MovementRequest {
        $request = MovementRequest::with('items')->findOrFail($requestId);

        if (!$request->isPending()) {
            throw new \Exception('Solo se pueden aprobar solicitudes pendientes');
        }

        return DB::transaction(function () use ($request, $approvedQuantities) {
            // Actualizar cantidades aprobadas
            if ($approvedQuantities) {
                foreach ($request->items as $item) {
                    if (isset($approvedQuantities[$item->id])) {
                        $item->update([
                            'quantity_approved' => $approvedQuantities[$item->id]
                        ]);
                    } else {
                        $item->update([
                            'quantity_approved' => $item->quantity_requested
                        ]);
                    }
                }
            } else {
                // Aprobar todas las cantidades solicitadas
                foreach ($request->items as $item) {
                    $item->update([
                        'quantity_approved' => $item->quantity_requested
                    ]);
                }
            }

            // Actualizar la solicitud
            $request->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            event(new MovementRequestApproved($request));

            return $request->fresh('items');
        });
    }

    /**
     * Rechazar solicitud
     */
    public function rejectRequest(int $requestId, string $reason): MovementRequest
    {
        $request = MovementRequest::with('items')->findOrFail($requestId);

        if (!$request->isPending()) {
            throw new \Exception('Solo se pueden rechazar solicitudes pendientes');
        }

        return DB::transaction(function () use ($request, $reason) {
            // Liberar stock reservado
            if ($request->type === 'transfer') {
                foreach ($request->items as $item) {
                    $this->stockService->unreserveStock(
                        $item->product_id,
                        $request->from_warehouse_id,
                        $item->quantity_requested
                    );
                }
            }

            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            event(new MovementRequestRejected($request));

            return $request->fresh();
        });
    }

    /**
     * Ejecutar transferencia aprobada
     */
    public function executeApprovedTransfer(int $requestId): array
    {
        $request = MovementRequest::with('items.product')->findOrFail($requestId);

        if (!$request->isApproved()) {
            throw new \Exception('Solo se pueden ejecutar solicitudes aprobadas');
        }

        if ($request->isCompleted()) {
            throw new \Exception('Esta solicitud ya fue ejecutada');
        }

        $movements = [];

        DB::transaction(function () use ($request, &$movements) {
            foreach ($request->items as $item) {
                $quantity = $item->quantity_approved ?? $item->quantity_requested;

                // Liberar la reserva
                $this->stockService->unreserveStock(
                    $item->product_id,
                    $request->from_warehouse_id,
                    $quantity
                );

                // Crear el movimiento
                $dto = new CreateMovementDTO(
                    type: MovementType::TRANSFER,
                    productId: $item->product_id,
                    quantity: $quantity,
                    fromWarehouseId: $request->from_warehouse_id,
                    toWarehouseId: $request->to_warehouse_id,
                    serialId: $item->serial_id,
                    referenceType: MovementRequest::class,
                    referenceId: $request->id,
                    notes: "Transferencia según solicitud {$request->code}"
                );

                $movements[] = $this->movementService->createMovement($dto);
            }

            // Marcar solicitud como completada
            $request->update(['status' => 'completed']);
        });

        return $movements;
    }

    /**
     * Obtener solicitudes pendientes
     */
    public function getPendingRequests(?int $userId = null)
    {
        $query = MovementRequest::with(['items.product', 'fromWarehouse', 'toWarehouse', 'requester'])
            ->pending()
            ->latest();

        if ($userId) {
            $query->where('requested_by', $userId);
        }

        return $query->get();
    }

    /**
     * Obtener solicitudes por usuario
     */
    public function getUserRequests(int $userId, ?string $status = null)
    {
        $query = MovementRequest::with(['items.product', 'fromWarehouse', 'toWarehouse'])
            ->where('requested_by', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate(20);
    }
}
