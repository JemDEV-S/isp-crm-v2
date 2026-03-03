@extends('layouts.app')

@section('title', $product->name)

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('inventory.products.index') }}" class="text-secondary-500 hover:text-secondary-700">Productos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $product->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $product->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge :variant="$product->is_active ? 'success' : 'danger'" dot>
                        {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                    </x-badge>
                    @if($product->requires_serial)
                        <x-badge variant="info">Requiere Serial</x-badge>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                @can('inventory.products.edit')
                    <a href="{{ route('inventory.products.edit', $product) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Tarjetas de Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-card>
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-primary-100 rounded-lg p-3">
                        <x-icon name="package" class="w-6 h-6 text-primary-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-500">Stock Total</p>
                        <p class="text-2xl font-bold text-secondary-900">{{ number_format($totalStock) }}</p>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-success-100 rounded-lg p-3">
                        <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-500">Disponible</p>
                        <p class="text-2xl font-bold text-secondary-900">{{ number_format($availableStock) }}</p>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-warning-100 rounded-lg p-3">
                        <x-icon name="clock" class="w-6 h-6 text-warning-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-500">Reservado</p>
                        <p class="text-2xl font-bold text-secondary-900">{{ number_format($reservedStock) }}</p>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Información General">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">SKU</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $product->sku }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Categoría</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $product->category->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Marca</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $product->brand ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Modelo</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $product->model ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Unidad de Medida</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $product->unit_of_measure }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Costo Unitario</dt>
                            <dd class="mt-1 text-sm text-secondary-900">S/ {{ number_format($product->unit_cost, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Stock Mínimo</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $product->min_stock }}</dd>
                        </div>
                        @if($product->description)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-secondary-500">Descripción</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $product->description }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                <x-card title="Stock por Almacén" :padding="false">
                    <x-table>
                        <x-slot name="header">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Almacén</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Cantidad</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Reservado</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Disponible</th>
                        </x-slot>
                        @forelse($product->stock as $stock)
                            <tr>
                                <td class="px-6 py-4 text-sm">{{ $stock->warehouse->name }}</td>
                                <td class="px-6 py-4 text-sm text-right">{{ number_format($stock->quantity) }}</td>
                                <td class="px-6 py-4 text-sm text-right">{{ number_format($stock->reserved_quantity) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-medium">{{ number_format($stock->available_quantity) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-4 text-center text-sm text-secondary-500">Sin stock en almacenes</td></tr>
                        @endforelse
                    </x-table>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Creado por</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $product->creator->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $product->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $product->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </x-card>
            </div>
        </div>
    </div>
@endsection
