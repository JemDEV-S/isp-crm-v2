@extends('layouts.app')

@section('title', 'Stock Bajo')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <a href="{{ route('inventory.stock.index') }}" class="text-secondary-500 hover:text-secondary-700">Stock</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <span class="text-secondary-900 font-medium">Stock Bajo</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Stock Bajo</h1>
                <p class="mt-1 text-sm text-secondary-500">Productos con stock por debajo del mínimo</p>
            </div>
            <a href="{{ route('inventory.stock.index') }}"><x-button variant="secondary" icon="arrow-left">Ver Todo el Stock</x-button></a>
        </div>

        @if($lowStock->count() > 0)
            <x-alert variant="warning" :dismissible="false">Se encontraron {{ $lowStock->total() }} productos con stock bajo. Considere reabastecer estos productos.</x-alert>
        @endif

        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Producto</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Almacén</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Stock Actual</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Stock Mínimo</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-secondary-700 uppercase">Estado</th>
                </x-slot>
                @forelse($lowStock as $item)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-secondary-900"><a href="{{ route('inventory.products.show', $item->product) }}" class="hover:text-primary-600">{{ $item->product->name }}</a></div>
                            <div class="text-sm text-secondary-500">SKU: {{ $item->product->sku }}</div>
                        </td>
                        <td class="px-6 py-4"><a href="{{ route('inventory.warehouses.show', $item->warehouse) }}" class="text-sm text-primary-600 hover:text-primary-900">{{ $item->warehouse->name }}</a></td>
                        <td class="px-6 py-4 text-right"><span class="text-sm font-medium text-danger-600">{{ number_format($item->quantity) }}</span></td>
                        <td class="px-6 py-4 text-right text-sm">{{ number_format($item->product->min_stock) }}</td>
                        <td class="px-6 py-4 text-center"><x-badge variant="danger" icon="exclamation-triangle">Crítico</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center"><div class="flex flex-col items-center"><x-icon name="check-circle" class="w-12 h-12 text-success-300" /><h3 class="mt-2 text-sm font-medium text-secondary-900">¡Excelente!</h3><p class="mt-1 text-sm text-secondary-500">No hay productos con stock bajo.</p></div></td></tr>
                @endforelse
            </x-table>
            @if($lowStock->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">{{ $lowStock->withQueryString()->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
