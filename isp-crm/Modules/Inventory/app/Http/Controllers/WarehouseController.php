<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Entities\Warehouse;
use Modules\Inventory\Enums\WarehouseType;
use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Entities\Zone;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.warehouse.index')->only('index');
        $this->middleware('permission:inventory.warehouse.create')->only(['create', 'store']);
        $this->middleware('permission:inventory.warehouse.edit')->only(['edit', 'update']);
        $this->middleware('permission:inventory.warehouse.delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Warehouse::with(['user', 'zone']);

        // Filtro por tipo
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filtro por estado
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Búsqueda
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $warehouses = $query->latest()->paginate(20);
        $types = WarehouseType::cases();

        return view('inventory::warehouses.index', compact('warehouses', 'types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $types = WarehouseType::cases();
        $technicians = User::whereHas('roles', function ($q) {
            $q->where('code', 'technician');
        })->orderBy('name')->get(['id', 'name']);
        $zones = Zone::orderBy('name')->get(['id', 'name']);

        return view('inventory::warehouses.create', compact('types', 'technicians', 'zones'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:100',
            'type' => 'required|string',
            'address' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'zone_id' => 'nullable|exists:zones,id',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $warehouse = Warehouse::create($validated);

        return redirect()
            ->route('inventory.warehouses.show', $warehouse)
            ->with('success', 'Almacén creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse)
    {
        $warehouse->load(['user', 'zone', 'stock.product']);

        $stats = [
            'total_products' => $warehouse->getTotalProducts(),
            'total_quantity' => $warehouse->getTotalQuantity(),
            'total_value' => $warehouse->getTotalValue(),
        ];

        return view('inventory::warehouses.show', compact('warehouse', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse)
    {
        $types = WarehouseType::cases();
        $technicians = User::whereHas('roles', function ($q) {
            $q->where('code', 'technician');
        })->orderBy('name')->get(['id', 'name']);
        $zones = Zone::orderBy('name')->get(['id', 'name']);

        return view('inventory::warehouses.edit', compact('warehouse', 'types', 'technicians', 'zones'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'name' => 'required|string|max:100',
            'type' => 'required|string',
            'address' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'zone_id' => 'nullable|exists:zones,id',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $warehouse->update($validated);

        return redirect()
            ->route('inventory.warehouses.show', $warehouse)
            ->with('success', 'Almacén actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        // Verificar que no tenga stock
        if ($warehouse->stock()->exists()) {
            return back()->with('error', 'No se puede eliminar un almacén con stock');
        }

        $warehouse->delete();

        return redirect()
            ->route('inventory.warehouses.index')
            ->with('success', 'Almacén eliminado exitosamente');
    }
}
