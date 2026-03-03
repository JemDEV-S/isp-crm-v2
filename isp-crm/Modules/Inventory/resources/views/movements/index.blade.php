@extends('layouts.app')

@section('title', 'Movimientos de Inventario')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <span class="text-secondary-900 font-medium">Movimientos</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Movimientos de Inventario</h1>
                <p class="mt-1 text-sm text-secondary-500">Historial de entradas y salidas</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('inventory.movements.adjustment') }}"><x-button variant="warning" icon="pencil">Ajuste de Stock</x-button></a>
                <a href="{{ route('inventory.movements.create') }}"><x-button icon="plus">Nuevo Movimiento</x-button></a>
            </div>
        </div>

        <x-card>
            <form method="GET" action="{{ route('inventory.movements.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <x-select name="type" label="Tipo" placeholder="Todos">
                        @foreach($types as $type)
                            <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                        @endforeach
                    </x-select>
                    <x-input type="date" name="start_date" label="Desde" :value="request('start_date')" />
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('inventory.movements.index') }}"><x-button variant="ghost" type="button">Limpiar</x-button></a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Producto</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Origen / Destino</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Cantidad</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase">Usuario</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase">Acciones</th>
                </x-slot>
                @forelse($movements as $movement)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap"><x-badge variant="{{ $movement->type->badgeVariant() }}" size="sm">{{ $movement->type->label() }}</x-badge></td>
                        <td class="px-6 py-4"><div class="text-sm font-medium text-secondary-900">{{ $movement->product->name }}</div><div class="text-sm text-secondary-500">{{ $movement->product->sku }}</div></td>
                        <td class="px-6 py-4 text-sm">
                            @if($movement->fromWarehouse)
                                <div class="text-secondary-500">De: {{ $movement->fromWarehouse->name }}</div>
                            @endif
                            @if($movement->toWarehouse)
                                <div class="text-secondary-900">A: {{ $movement->toWarehouse->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right"><span class="text-sm font-medium {{ $movement->isPositive() ? 'text-success-600' : 'text-danger-600' }}">{{ $movement->isPositive() ? '+' : '-' }}{{ number_format($movement->quantity) }}</span></td>
                        <td class="px-6 py-4 text-sm">{{ $movement->user->name }}</td>
                        <td class="px-6 py-4 text-right"><a href="{{ route('inventory.movements.show', $movement) }}" class="text-primary-600 hover:text-primary-900"><x-icon name="eye" class="w-5 h-5 inline" /></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center"><div class="flex flex-col items-center"><x-icon name="clipboard" class="w-12 h-12 text-secondary-300" /><h3 class="mt-2 text-sm font-medium text-secondary-900">No hay movimientos</h3><p class="mt-1 text-sm text-secondary-500">No se encontraron movimientos.</p></div></td></tr>
                @endforelse
            </x-table>
            @if($movements->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">{{ $movements->withQueryString()->links() }}</div>
            @endif
        </x-card>
    </div>
@endsection
