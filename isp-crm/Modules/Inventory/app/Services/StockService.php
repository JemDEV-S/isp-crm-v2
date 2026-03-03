<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Inventory\Entities\Product;
use Modules\Inventory\Entities\Stock;
use Modules\Inventory\Entities\Warehouse;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Events\StockLow;

class StockService
{
    /**
     * Obtener o crear stock de un producto en un almacén
     */
    public function getOrCreateStock(int $productId, int $warehouseId): Stock
    {
        return Stock::firstOrCreate(
            [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );
    }

    /**
     * Incrementar stock en un almacén
     */
    public function addStock(int $productId, int $warehouseId, float $quantity): Stock
    {
        $stock = $this->getOrCreateStock($productId, $warehouseId);
        $stock->add($quantity);

        return $stock->fresh();
    }

    /**
     * Decrementar stock en un almacén
     */
    public function subtractStock(int $productId, int $warehouseId, float $quantity): Stock
    {
        $stock = $this->getOrCreateStock($productId, $warehouseId);

        if ($stock->available_quantity < $quantity) {
            throw new \Exception("Stock insuficiente en el almacén. Disponible: {$stock->available_quantity}");
        }

        $stock->subtract($quantity);

        // Verificar si está bajo el mínimo
        if ($stock->isLowStock()) {
            event(new StockLow($stock));
        }

        return $stock->fresh();
    }

    /**
     * Reservar stock para una orden
     */
    public function reserveStock(int $productId, int $warehouseId, float $quantity): Stock
    {
        $stock = $this->getOrCreateStock($productId, $warehouseId);
        $stock->reserve($quantity);

        return $stock->fresh();
    }

    /**
     * Liberar stock reservado
     */
    public function unreserveStock(int $productId, int $warehouseId, float $quantity): Stock
    {
        $stock = $this->getOrCreateStock($productId, $warehouseId);
        $stock->unreserve($quantity);

        return $stock->fresh();
    }

    /**
     * Transferir stock entre almacenes
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity
    ): array {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity) {
            // Restar del origen
            $fromStock = $this->subtractStock($productId, $fromWarehouseId, $quantity);

            // Sumar al destino
            $toStock = $this->addStock($productId, $toWarehouseId, $quantity);

            return [
                'from' => $fromStock,
                'to' => $toStock,
            ];
        });
    }

    /**
     * Obtener productos con stock bajo
     */
    public function getLowStockProducts(?int $warehouseId = null)
    {
        $query = Product::with(['stock' => function ($q) use ($warehouseId) {
            if ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        }])
        ->active()
        ->whereHas('stock', function ($q) use ($warehouseId) {
            $q->whereRaw('quantity - reserved_quantity < products.min_stock');
            if ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        });

        return $query->get();
    }

    /**
     * Obtener resumen de stock por almacén
     */
    public function getStockSummary(?int $warehouseId = null): array
    {
        $query = Stock::with(['product', 'warehouse']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $stocks = $query->get();

        return [
            'total_products' => $stocks->pluck('product_id')->unique()->count(),
            'total_quantity' => $stocks->sum('quantity'),
            'total_reserved' => $stocks->sum('reserved_quantity'),
            'total_available' => $stocks->sum(fn($s) => $s->available_quantity),
            'total_value' => $stocks->sum(fn($s) => $s->quantity * $s->product->unit_cost),
            'low_stock_count' => $stocks->filter(fn($s) => $s->isLowStock())->count(),
        ];
    }

    /**
     * Verificar disponibilidad de stock
     */
    public function checkAvailability(int $productId, int $warehouseId, float $quantity): bool
    {
        $stock = Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$stock) {
            return false;
        }

        return $stock->available_quantity >= $quantity;
    }
}
