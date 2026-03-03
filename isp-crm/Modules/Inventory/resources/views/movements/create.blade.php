@extends('layouts.app')

@section('title', 'Nuevo Movimiento')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <a href="{{ route('inventory.movements.index') }}" class="text-secondary-500 hover:text-secondary-700">Movimientos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <span class="text-secondary-900 font-medium">Nuevo Movimiento</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Nuevo Movimiento de Inventario</h1>
            <p class="mt-1 text-sm text-secondary-500">Registre una entrada, salida o transferencia</p>
        </div>

        <form action="{{ route('inventory.movements.store') }}" method="POST" x-data="{ type: '{{ old('type', $type) }}', requiresSerial: false }">
            @csrf
            <x-card title="Información del Movimiento" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-select name="type" label="Tipo de Movimiento" :error="$errors->first('type')" required x-model="type">
                            @foreach($types as $movType)
                                <option value="{{ $movType->value }}">{{ $movType->label() }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="md:col-span-2">
                        <x-select name="product_id" label="Producto" :error="$errors->first('product_id')" required>
                            <option value="">Seleccione un producto</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-requires-serial="{{ $product->requires_serial ? '1' : '0' }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }} - {{ $product->sku }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <x-input type="number" name="quantity" label="Cantidad" :value="old('quantity')" :error="$errors->first('quantity')" required min="1" step="1" />
                    <x-input type="number" name="unit_cost" label="Costo Unitario (S/)" :value="old('unit_cost')" step="0.01" min="0" hint="Opcional" />

                    <div x-show="type === 'transfer' || type === 'sale' || type === 'installation'" class="md:col-span-2">
                        <x-select name="from_warehouse_id" label="Almacén Origen" :error="$errors->first('from_warehouse_id')">
                            <option value="">Seleccione almacén origen</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ old('from_warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div x-show="type === 'purchase' || type === 'transfer' || type === 'return'" class="md:col-span-2">
                        <x-select name="to_warehouse_id" label="Almacén Destino" :error="$errors->first('to_warehouse_id')">
                            <option value="">Seleccione almacén destino</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ old('to_warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">Notas</label>
                        <textarea name="notes" id="notes" rows="3" class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500" placeholder="Detalles adicionales del movimiento">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </x-card>

            <div class="flex justify-end gap-3">
                <a href="{{ route('inventory.movements.index') }}"><x-button variant="ghost" type="button">Cancelar</x-button></a>
                <x-button type="submit" icon="check">Registrar Movimiento</x-button>
            </div>
        </form>
    </div>
@endsection
