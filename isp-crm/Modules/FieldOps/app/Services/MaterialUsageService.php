<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Services;

use Modules\FieldOps\app\Models\MaterialUsage;
use Modules\FieldOps\app\Models\WorkOrder;

class MaterialUsageService
{
    public function record(
        WorkOrder $workOrder,
        int $productId,
        float $quantity,
        int $warehouseId,
        ?int $serialId = null,
        ?string $notes = null,
        int $recordedBy = 0
    ): MaterialUsage {
        return MaterialUsage::create([
            'work_order_id' => $workOrder->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'warehouse_id' => $warehouseId,
            'serial_id' => $serialId,
            'notes' => $notes,
            'recorded_by' => $recordedBy ?: auth()->id(),
        ]);
    }

    public function getTotalCost(WorkOrder $workOrder): float
    {
        return $workOrder->materialUsages()
            ->with('product')
            ->get()
            ->sum(function ($usage) {
                return $usage->quantity * $usage->product->unit_cost;
            });
    }
}
