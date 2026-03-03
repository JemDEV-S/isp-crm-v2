<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Entities\Stock;
use Modules\Inventory\Entities\Product;
use Modules\Inventory\Entities\Warehouse;

class StockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.stock.index')->only(['index', 'lowStock', 'byWarehouse']);
    }

    /**
     * Display a listing of stock.
     */
    public function index(Request $request)
    {
        $query = Stock::with(['product.category', 'warehouse']);

        // Filtro por producto
        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        // Filtro por almacén
        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Solo con stock disponible
        if ($request->boolean('available_only')) {
            $query->where('quantity', '>', 0);
        }

        // Búsqueda
        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $stock = $query->latest('updated_at')->paginate(50);

        $products = Product::active()->orderBy('name')->get(['id', 'name', 'sku']);
        $warehouses = Warehouse::active()->orderBy('name')->get(['id', 'name']);

        return view('inventory::stock.index', compact('stock', 'products', 'warehouses'));
    }

    /**
     * Display products with low stock.
     */
    public function lowStock(Request $request)
    {
        $query = Stock::with(['product.category', 'warehouse'])
            ->join('products', 'products.id', '=', 'stock.product_id')
            ->whereRaw('stock.quantity <= products.min_stock')
            ->select('stock.*');

        // Filtro por almacén
        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('stock.warehouse_id', $warehouseId);
        }

        $lowStock = $query->latest('stock.updated_at')->paginate(50);
        $warehouses = Warehouse::active()->orderBy('name')->get(['id', 'name']);

        return view('inventory::stock.low-stock', compact('lowStock', 'warehouses'));
    }

    /**
     * Display stock for a specific warehouse.
     */
    public function byWarehouse(Warehouse $warehouse, Request $request)
    {
        $query = Stock::with(['product.category'])
            ->where('warehouse_id', $warehouse->id);

        // Solo con stock disponible
        if ($request->boolean('available_only')) {
            $query->where('quantity', '>', 0);
        }

        // Búsqueda
        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $stock = $query->latest('updated_at')->paginate(50);

        return view('inventory::stock.by-warehouse', compact('warehouse', 'stock'));
    }
}
