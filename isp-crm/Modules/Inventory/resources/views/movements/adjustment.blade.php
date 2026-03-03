@extends('layouts.app')

@section('title', 'Ajuste de Inventario')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <a href="{{ route('inventory.movements.index') }}" class="text-secondary-500 hover:text-secondary-700">Movimientos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <span class="text-secondary-900 font-medium">Ajuste de Inventario</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Ajuste de Inventario</h1>
            <p class="mt-1 text-sm text-secondary-500">Corregir diferencias en el stock físico vs sistema</p>
        </div>

        <x-alert variant="warning" class="mb-6">
            Los ajustes de inventario deben realizarse con precaución. Asegúrese de contar físicamente el stock antes de realizar un ajuste.
        </x-alert>

        <form action="{{ route('inventory.movements.storeAdjustment') }}" method="POST">
            @csrf
            <x-card title="Información del Ajuste" class="mb-6">
                <div class="space-y-6">
                    <x-select name="product_id" label="Producto" :error="$errors->first('product_id')" required>
                        <option value="">Seleccione un producto</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} - SKU: {{ $product->sku }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="warehouse_id" label="Almacén" :error="$errors->first('warehouse_id')" required>
                        <option value="">Seleccione un almacén</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <div>
                        <x-input
                            type="number"
                            name="quantity"
                            label="Cantidad de Ajuste"
                            :value="old('quantity')"
                            :error="$errors->first('quantity')"
                            required
                            step="1"
                            hint="Use números positivos para aumentar stock y negativos para disminuir"
                            placeholder="Ej: +5 o -3"
                        />
                        <p class="mt-2 text-xs text-secondary-500">
                            <strong>Ejemplo:</strong> Si el sistema muestra 10 unidades pero físicamente hay 13, ingrese <code>+3</code>
                        </p>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                            Motivo del Ajuste <span class="text-danger-600">*</span>
                        </label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="4"
                            required
                            class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Explique el motivo del ajuste: conteo físico, diferencia detectada, producto dañado, etc."
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-secondary-500">
                            Describa claramente el motivo del ajuste para mantener trazabilidad
                        </p>
                    </div>
                </div>
            </x-card>

            <x-card title="Confirmación" class="mb-6">
                <label class="inline-flex items-center">
                    <input
                        type="checkbox"
                        required
                        class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                    <span class="ml-2 text-sm text-secondary-700">
                        Confirmo que he verificado físicamente el stock y el ajuste es necesario
                    </span>
                </label>
            </x-card>

            <div class="flex justify-end gap-3">
                <a href="{{ route('inventory.movements.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" variant="warning" icon="check">
                    Registrar Ajuste
                </x-button>
            </div>
        </form>
    </div>
@endsection
