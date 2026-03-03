@extends('layouts.app')

@section('title', 'Detalle del Movimiento')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <a href="{{ route('inventory.movements.index') }}" class="text-secondary-500 hover:text-secondary-700">Movimientos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <span class="text-secondary-900 font-medium">Detalle</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Movimiento #{{ $movement->id }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge variant="{{ $movement->type->badgeVariant() }}">{{ $movement->type->label() }}</x-badge>
                </div>
            </div>
            <a href="{{ route('inventory.movements.index') }}"><x-button variant="secondary" icon="arrow-left">Volver</x-button></a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Información del Movimiento">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Producto</dt>
                            <dd class="mt-1"><a href="{{ route('inventory.products.show', $movement->product) }}" class="text-sm text-primary-600 hover:text-primary-900">{{ $movement->product->name }}</a></dd>
                            <dd class="text-xs text-secondary-500">SKU: {{ $movement->product->sku }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Cantidad</dt>
                            <dd class="mt-1"><span class="text-sm font-medium {{ $movement->isPositive() ? 'text-success-600' : 'text-danger-600' }}">{{ $movement->isPositive() ? '+' : '-' }}{{ number_format($movement->quantity) }}</span></dd>
                        </div>
                        @if($movement->fromWarehouse)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Almacén Origen</dt>
                                <dd class="mt-1"><a href="{{ route('inventory.warehouses.show', $movement->fromWarehouse) }}" class="text-sm text-primary-600 hover:text-primary-900">{{ $movement->fromWarehouse->name }}</a></dd>
                            </div>
                        @endif
                        @if($movement->toWarehouse)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Almacén Destino</dt>
                                <dd class="mt-1"><a href="{{ route('inventory.warehouses.show', $movement->toWarehouse) }}" class="text-sm text-primary-600 hover:text-primary-900">{{ $movement->toWarehouse->name }}</a></dd>
                            </div>
                        @endif
                        @if($movement->unit_cost)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Costo Unitario</dt>
                                <dd class="mt-1 text-sm text-secondary-900">S/ {{ number_format($movement->unit_cost, 2) }}</dd>
                            </div>
                        @endif
                        @if($movement->serial)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Número de Serie</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $movement->serial->serial_number }}</dd>
                            </div>
                        @endif
                        @if($movement->notes)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-secondary-500">Notas</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $movement->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Registrado por</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $movement->user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha y Hora</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $movement->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        @if($movement->approver)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Aprobado por</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $movement->approver->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Fecha de Aprobación</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $movement->approved_at->format('d/m/Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>
            </div>
        </div>
    </div>
@endsection
