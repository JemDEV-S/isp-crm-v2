@extends('layouts.app')

@section('title', $warehouse->name)

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <a href="{{ route('inventory.warehouses.index') }}" class="text-secondary-500 hover:text-secondary-700">Almacenes</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <span class="text-secondary-900 font-medium">{{ $warehouse->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $warehouse->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge :variant="$warehouse->is_active ? 'success' : 'danger'" dot>{{ $warehouse->is_active ? 'Activo' : 'Inactivo' }}</x-badge>
                    <x-badge variant="info">{{ $warehouse->type->label() }}</x-badge>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('inventory.stock.byWarehouse', $warehouse) }}"><x-button variant="secondary" icon="package">Ver Stock</x-button></a>
                @can('inventory.warehouse.edit')
                    <a href="{{ route('inventory.warehouses.edit', $warehouse) }}"><x-button variant="secondary" icon="pencil">Editar</x-button></a>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-card>
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-primary-100 rounded-lg p-3">
                        <x-icon name="package" class="w-6 h-6 text-primary-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-500">Productos</p>
                        <p class="text-2xl font-bold text-secondary-900">{{ $stats['total_products'] }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-success-100 rounded-lg p-3">
                        <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-500">Unidades</p>
                        <p class="text-2xl font-bold text-secondary-900">{{ number_format($stats['total_quantity']) }}</p>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-warning-100 rounded-lg p-3">
                        <x-icon name="currency" class="w-6 h-6 text-warning-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-500">Valor Total</p>
                        <p class="text-2xl font-bold text-secondary-900">S/ {{ number_format($stats['total_value'], 2) }}</p>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Stock" :padding="false">
                    <x-table>
                        <x-slot name="header">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Producto</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Cantidad</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Disponible</th>
                        </x-slot>
                        @forelse($warehouse->stock->take(10) as $stock)
                            <tr>
                                <td class="px-6 py-4 text-sm"><a href="{{ route('inventory.products.show', $stock->product) }}" class="text-primary-600 hover:text-primary-900">{{ $stock->product->name }}</a></td>
                                <td class="px-6 py-4 text-sm text-right">{{ number_format($stock->quantity) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-medium">{{ number_format($stock->available_quantity) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-secondary-500">Sin stock</td></tr>
                        @endforelse
                    </x-table>
                    @if($warehouse->stock->count() > 10)
                        <div class="px-6 py-3 bg-secondary-50 text-center">
                            <a href="{{ route('inventory.stock.byWarehouse', $warehouse) }}" class="text-sm text-primary-600 hover:text-primary-900">Ver todo el stock ({{ $warehouse->stock->count() }} productos)</a>
                        </div>
                    @endif
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Información General">
                    <dl class="space-y-4">
                        <div><dt class="text-sm font-medium text-secondary-500">Código</dt><dd class="mt-1 text-sm text-secondary-900">{{ $warehouse->code }}</dd></div>
                        @if($warehouse->address)<div><dt class="text-sm font-medium text-secondary-500">Dirección</dt><dd class="mt-1 text-sm text-secondary-900">{{ $warehouse->address }}</dd></div>@endif
                        @if($warehouse->zone)<div><dt class="text-sm font-medium text-secondary-500">Zona</dt><dd class="mt-1 text-sm text-secondary-900">{{ $warehouse->zone->name }}</dd></div>@endif
                        @if($warehouse->user)<div><dt class="text-sm font-medium text-secondary-500">Técnico Asignado</dt><dd class="mt-1 text-sm text-secondary-900">{{ $warehouse->user->name }}</dd></div>@endif
                        @if($warehouse->contact_name)<div><dt class="text-sm font-medium text-secondary-500">Contacto</dt><dd class="mt-1 text-sm text-secondary-900">{{ $warehouse->contact_name }}</dd></div>@endif
                        @if($warehouse->contact_phone)<div><dt class="text-sm font-medium text-secondary-500">Teléfono</dt><dd class="mt-1 text-sm text-secondary-900">{{ $warehouse->contact_phone }}</dd></div>@endif
                    </dl>
                </x-card>
            </div>
        </div>
    </div>
@endsection
