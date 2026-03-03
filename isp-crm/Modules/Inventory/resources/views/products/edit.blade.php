@extends('layouts.app')

@section('title', 'Editar Producto')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('inventory.products.index') }}" class="text-secondary-500 hover:text-secondary-700">Productos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Editar Producto</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Producto</h1>
            <p class="mt-1 text-sm text-secondary-500">Modifique la información del producto</p>
        </div>

        <form action="{{ route('inventory.products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')

            <x-card title="Información Básica" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="name"
                        label="Nombre del Producto"
                        :value="old('name', $product->name)"
                        :error="$errors->first('name')"
                        required
                    />

                    <x-input
                        name="sku"
                        label="SKU / Código"
                        :value="old('sku', $product->sku)"
                        :error="$errors->first('sku')"
                        required
                    />

                    <div class="md:col-span-2">
                        <x-select name="category_id" label="Categoría" :error="$errors->first('category_id')" required>
                            <option value="">Seleccione una categoría</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">Descripción</label>
                        <textarea name="description" id="description" rows="3" class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </x-card>

            <x-card title="Especificaciones Técnicas" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input name="brand" label="Marca" :value="old('brand', $product->brand)" />
                    <x-input name="model" label="Modelo" :value="old('model', $product->model)" />
                    <x-input name="unit_of_measure" label="Unidad de Medida" :value="old('unit_of_measure', $product->unit_of_measure)" required />
                </div>
            </x-card>

            <x-card title="Inventario y Costos" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input type="number" name="unit_cost" label="Costo Unitario (S/)" :value="old('unit_cost', $product->unit_cost)" required step="0.01" min="0" />
                    <x-input type="number" name="min_stock" label="Stock Mínimo" :value="old('min_stock', $product->min_stock)" min="0" />
                    <div class="md:col-span-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="requires_serial" value="1" {{ old('requires_serial', $product->requires_serial) ? 'checked' : '' }} class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Requiere número de serie / MAC</span>
                        </label>
                    </div>
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Producto activo</span>
                        </label>
                    </div>
                </div>
            </x-card>

            <div class="flex justify-end gap-3">
                <a href="{{ route('inventory.products.show', $product) }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
