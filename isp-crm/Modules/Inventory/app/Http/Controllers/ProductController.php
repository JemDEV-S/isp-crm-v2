<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Entities\Product;
use Modules\Inventory\Entities\ProductCategory;
use Modules\Inventory\Http\Requests\StoreProductRequest;
use Modules\Inventory\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.products.index')->only('index');
        $this->middleware('permission:inventory.products.create')->only(['create', 'store']);
        $this->middleware('permission:inventory.products.edit')->only(['edit', 'update']);
        $this->middleware('permission:inventory.products.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'creator'])
            ->withCount('stock');

        // Búsqueda
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filtro por categoría
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filtro por estado
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Solo productos con serial
        if ($request->boolean('requires_serial')) {
            $query->requiresSerial();
        }

        $products = $query->latest()->paginate(20);
        $categories = ProductCategory::active()->orderBy('name')->get();

        return view('inventory::products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = ProductCategory::active()->orderBy('name')->get();

        return view('inventory::products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $product = Product::create($data);

        return redirect()
            ->route('inventory.products.show', $product)
            ->with('success', 'Producto creado exitosamente');
    }

    public function show(Product $product)
    {
        $product->load([
            'category',
            'stock.warehouse',
            'serials' => fn($q) => $q->latest()->limit(50),
            'movements' => fn($q) => $q->with(['user', 'fromWarehouse', 'toWarehouse'])->latest()->limit(20)
        ]);

        $totalStock = $product->stock->sum('quantity');
        $availableStock = $product->stock->sum(fn($s) => $s->available_quantity);
        $reservedStock = $product->stock->sum('reserved_quantity');

        return view('inventory::products.show', compact(
            'product',
            'totalStock',
            'availableStock',
            'reservedStock'
        ));
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::active()->orderBy('name')->get();

        return view('inventory::products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()
            ->route('inventory.products.show', $product)
            ->with('success', 'Producto actualizado exitosamente');
    }

    public function destroy(Product $product)
    {
        // Verificar que no tenga stock
        if ($product->total_stock > 0) {
            return back()->with('error', 'No se puede eliminar un producto con stock');
        }

        $product->delete();

        return redirect()
            ->route('inventory.products.index')
            ->with('success', 'Producto eliminado exitosamente');
    }
}
