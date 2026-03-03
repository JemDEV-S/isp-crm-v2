@extends('layouts.app')

@section('title', 'Stock')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Stock</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Stock</h1>
                <p class="mt-1 text-sm text-secondary-500">Consulta de inventario general</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('inventory.stock.lowStock') }}">
                    <x-button variant="warning" icon="exclamation-triangle">Stock Bajo</x-button>
                </a>
            </div>
        </div>

        <x-card>
            <form method="GET" action="{{ route('inventory.stock.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input name="search" label="Buscar Producto" placeholder="Nombre, SKU..." :value="request('search')" icon="search" />
                    <x-select name="product_id" label="Producto" placeholder="Todos">
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select name="warehouse_id" label="Almacén" placeholder="Todos">
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                        @endforeach
                    </x-select>
                    <div class="flex items-end">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="available_only" value="1" {{ request('available_only') ? 'checked' : '' }} class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Solo disponible</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('inventory.stock.index') }}"><x-button variant="ghost" type="button">Limpiar</x-button></a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Producto</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Almacén</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Cantidad</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Reservado</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Disponible</th>
                </x-slot>
                @forelse($stock as $item)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-secondary-900">
                                <a href="{{ route('inventory.products.show', $item->product) }}" class="hover:text-primary-600">{{ $item->product->name }}</a>
                            </div>
                            <div class="text-sm text-secondary-500">SKU: {{ $item->product->sku }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('inventory.warehouses.show', $item->warehouse) }}" class="text-sm text-primary-600 hover:text-primary-900">{{ $item->warehouse->name }}</a>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">{{ number_format($item->quantity) }}</td>
                        <td class="px-6 py-4 text-right text-sm">{{ number_format($item->reserved_quantity) }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <span class="font-medium {{ $item->available_quantity > 0 ? 'text-success-600' : 'text-danger-600' }}">{{ number_format($item->available_quantity) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center"><div class="flex flex-col items-center"><x-icon name="package" class="w-12 h-12 text-secondary-300" /><h3 class="mt-2 text-sm font-medium text-secondary-900">No hay stock</h3><p class="mt-1 text-sm text-secondary-500">No se encontraron resultados.</p></div></td></tr>
                @endforelse
            </x-table>
            @if($stock->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">{{ $stock->withQueryString()->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
