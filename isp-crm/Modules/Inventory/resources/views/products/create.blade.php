@extends('layouts.app')

@section('title', 'Crear Producto')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('inventory.products.index') }}" class="text-secondary-500 hover:text-secondary-700">Productos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Crear Producto</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Producto</h1>
            <p class="mt-1 text-sm text-secondary-500">Complete la información para crear un nuevo producto</p>
        </div>

        <!-- Formulario -->
        <form action="{{ route('inventory.products.store') }}" method="POST">
            @csrf

            <!-- Sección 1: Información Básica -->
            <x-card title="Información Básica" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="name"
                        label="Nombre del Producto"
                        :value="old('name')"
                        :error="$errors->first('name')"
                        required
                        placeholder="Ej: Router Mikrotik RB750"
                    />

                    <x-input
                        name="sku"
                        label="SKU / Código"
                        :value="old('sku')"
                        :error="$errors->first('sku')"
                        required
                        placeholder="Ej: MIKRO-RB750"
                    />

                    <div class="md:col-span-2">
                        <x-select
                            name="category_id"
                            label="Categoría"
                            :error="$errors->first('category_id')"
                            required
                        >
                            <option value="">Seleccione una categoría</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Descripción
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Descripción detallada del producto"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <!-- Sección 2: Especificaciones Técnicas -->
            <x-card title="Especificaciones Técnicas" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="brand"
                        label="Marca"
                        :value="old('brand')"
                        :error="$errors->first('brand')"
                        placeholder="Ej: Mikrotik"
                    />

                    <x-input
                        name="model"
                        label="Modelo"
                        :value="old('model')"
                        :error="$errors->first('model')"
                        placeholder="Ej: RB750Gr3"
                    />

                    <x-input
                        name="unit_of_measure"
                        label="Unidad de Medida"
                        :value="old('unit_of_measure', 'unidad')"
                        :error="$errors->first('unit_of_measure')"
                        required
                        placeholder="Ej: unidad, metro, caja"
                    />
                </div>
            </x-card>

            <!-- Sección 3: Inventario y Costos -->
            <x-card title="Inventario y Costos" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="number"
                        name="unit_cost"
                        label="Costo Unitario (S/)"
                        :value="old('unit_cost')"
                        :error="$errors->first('unit_cost')"
                        required
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                    />

                    <x-input
                        type="number"
                        name="min_stock"
                        label="Stock Mínimo"
                        :value="old('min_stock', 0)"
                        :error="$errors->first('min_stock')"
                        min="0"
                        hint="Notificar cuando el stock esté por debajo de este valor"
                    />

                    <div class="md:col-span-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="requires_serial" value="1"
                                   {{ old('requires_serial') ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Requiere número de serie / MAC</span>
                        </label>
                        <p class="mt-1 text-xs text-secondary-500">Marque esta opción para equipos como ONUs, routers, etc.</p>
                    </div>

                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Producto activo</span>
                        </label>
                    </div>
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('inventory.products.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Crear Producto</x-button>
            </div>
        </form>
    </div>
@endsection
