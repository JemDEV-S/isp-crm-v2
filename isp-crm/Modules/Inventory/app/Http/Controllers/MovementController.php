<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Services\MovementService;
use Modules\Inventory\Services\StockService;
use Modules\Inventory\Entities\Movement;
use Modules\Inventory\Entities\Product;
use Modules\Inventory\Entities\Warehouse;
use Modules\Inventory\DTOs\CreateMovementDTO;
use Modules\Inventory\Enums\MovementType;
use Modules\Inventory\Http\Requests\StoreMovementRequest;

class MovementController extends Controller
{
    public function __construct(
        private MovementService $movementService,
        private StockService $stockService
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Movement::with(['product', 'fromWarehouse', 'toWarehouse', 'user'])
            ->latest();

        // Filtros
        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->inWarehouse($warehouseId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $movements = $query->paginate(50);

        $products = Product::active()->orderBy('name')->get(['id', 'name', 'sku']);
        $warehouses = Warehouse::active()->orderBy('name')->get(['id', 'name']);
        $types = MovementType::cases();

        return view('inventory::movements.index', compact(
            'movements',
            'products',
            'warehouses',
            'types'
        ));
    }

    public function create(Request $request)
    {
        $type = $request->input('type', 'purchase');

        $products = Product::active()->orderBy('name')->get(['id', 'name', 'sku', 'requires_serial']);
        $warehouses = Warehouse::active()->orderBy('name')->get(['id', 'name', 'type']);
        $types = MovementType::cases();

        return view('inventory::movements.create', compact(
            'type',
            'products',
            'warehouses',
            'types'
        ));
    }

    public function store(StoreMovementRequest $request)
    {
        try {
            $dto = CreateMovementDTO::fromArray($request->validated());
            $movement = $this->movementService->createMovement($dto);

            return redirect()
                ->route('inventory.movements.show', $movement)
                ->with('success', 'Movimiento registrado exitosamente');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(Movement $movement)
    {
        $movement->load([
            'product',
            'fromWarehouse',
            'toWarehouse',
            'serial',
            'user',
            'approver'
        ]);

        return view('inventory::movements.show', compact('movement'));
    }

    /**
     * Página de ajuste de inventario
     */
    public function adjustment()
    {
        $products = Product::active()->orderBy('name')->get(['id', 'name', 'sku']);
        $warehouses = Warehouse::active()->orderBy('name')->get(['id', 'name']);

        return view('inventory::movements.adjustment', compact('products', 'warehouses'));
    }

    /**
     * Procesar ajuste
     */
    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric',
            'notes' => 'required|string',
        ]);

        try {
            $movement = $this->movementService->createAdjustment(
                productId: $validated['product_id'],
                warehouseId: $validated['warehouse_id'],
                quantity: (float) $validated['quantity'],
                isPositive: $validated['quantity'] > 0,
                notes: $validated['notes']
            );

            return redirect()
                ->route('inventory.movements.show', $movement)
                ->with('success', 'Ajuste de inventario registrado');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Verificar disponibilidad de stock
     */
    public function checkStock(Request $request)
    {
        $available = $this->stockService->checkAvailability(
            $request->input('product_id'),
            $request->input('warehouse_id'),
            $request->input('quantity')
        );

        return response()->json(['available' => $available]);
    }
}
