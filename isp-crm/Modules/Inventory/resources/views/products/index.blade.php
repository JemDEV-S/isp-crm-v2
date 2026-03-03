@extends('layouts.app')

@section('title', 'Productos')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Productos</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Botón Crear -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Productos</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestión del catálogo de productos del inventario</p>
            </div>
            @can('inventory.products.create')
                <a href="{{ route('inventory.products.create') }}">
                    <x-button icon="plus">
                        Nuevo Producto
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('inventory.products.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre, SKU..."
                        :value="request('search')"
                        icon="search"
                    />

                    <x-select name="category_id" label="Categoría" placeholder="Todas">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="is_active" label="Estado" placeholder="Todos">
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </x-select>

                    <div class="flex items-end">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="requires_serial" value="1"
                                   {{ request('requires_serial') ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Solo con serial</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('inventory.products.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Tabla de Productos -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Producto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Categoría
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Stock Total
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Costo Unitario
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($products as $product)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-secondary-900">{{ $product->name }}</div>
                                    <div class="text-sm text-secondary-500">SKU: {{ $product->sku }}</div>
                                    @if($product->requires_serial)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                            Serial
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">{{ $product->category->name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-medium text-secondary-900">
                                {{ number_format($product->total_stock, 0) }}
                            </div>
                            @if($product->hasLowStock())
                                <span class="text-xs text-danger-600">Stock bajo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm text-secondary-900">S/ {{ number_format($product->unit_cost, 2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <x-badge :variant="$product->is_active ? 'success' : 'danger'" dot>
                                {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('inventory.products.show', $product) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('inventory.products.edit')
                                    <a href="{{ route('inventory.products.edit', $product) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('inventory.products.delete')
                                    <form action="{{ route('inventory.products.destroy', $product) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este producto?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger-600 hover:text-danger-900">
                                            <x-icon name="trash" class="w-5 h-5" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="package" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay productos</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo producto.</p>
                                @can('inventory.products.create')
                                    <div class="mt-4">
                                        <a href="{{ route('inventory.products.create') }}">
                                            <x-button icon="plus">Nuevo Producto</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            <!-- Paginación -->
            @if($products->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $products->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
