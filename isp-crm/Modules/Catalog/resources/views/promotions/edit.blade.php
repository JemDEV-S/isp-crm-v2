@extends('layouts.app')

@section('title', 'Editar Promoción')

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('catalog.promotions.index') }}" class="text-secondary-500 hover:text-secondary-700">Promociones</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Editar: {{ $promotion->name }}</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Promoción: {{ $promotion->name }}</h1>
            <p class="mt-1 text-sm text-secondary-500">Modifique la información de la promoción</p>
        </div>

        <!-- Formulario -->
        <form action="{{ route('catalog.promotions.update', $promotion) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Sección 1: Información Básica -->
            <x-card title="Información Básica" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="code"
                        label="Código"
                        :value="old('code', $promotion->code)"
                        :error="$errors->first('code')"
                        required
                        hint="Código único de la promoción"
                    />

                    <x-input
                        name="name"
                        label="Nombre"
                        :value="old('name', $promotion->name)"
                        :error="$errors->first('name')"
                        required
                    />

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Descripción
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                        >{{ old('description', $promotion->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <!-- Sección 2: Configuración del Descuento -->
            <x-card title="Configuración del Descuento" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-select
                        name="discount_type"
                        label="Tipo de Descuento"
                        :error="$errors->first('discount_type')"
                        required
                    >
                        <option value="">Seleccione el tipo</option>
                        <option value="percentage" {{ old('discount_type', $promotion->discount_type->value) == 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                        <option value="fixed" {{ old('discount_type', $promotion->discount_type->value) == 'fixed' ? 'selected' : '' }}>Monto Fijo</option>
                    </x-select>

                    <x-input
                        type="number"
                        step="0.01"
                        name="discount_value"
                        label="Valor del Descuento"
                        :value="old('discount_value', $promotion->discount_value)"
                        :error="$errors->first('discount_value')"
                        required
                        hint="% si es porcentaje, S/ si es monto fijo"
                    />

                    <x-select
                        name="applies_to"
                        label="Aplicable a"
                        :error="$errors->first('applies_to')"
                        required
                    >
                        <option value="">Seleccione dónde aplicar</option>
                        <option value="monthly" {{ old('applies_to', $promotion->applies_to->value) == 'monthly' ? 'selected' : '' }}>Precio Mensual</option>
                        <option value="installation" {{ old('applies_to', $promotion->applies_to->value) == 'installation' ? 'selected' : '' }}>Instalación</option>
                        <option value="both" {{ old('applies_to', $promotion->applies_to->value) == 'both' ? 'selected' : '' }}>Ambos</option>
                    </x-select>

                    <x-input
                        type="number"
                        name="min_months"
                        label="Meses Mínimos"
                        :value="old('min_months', $promotion->min_months)"
                        :error="$errors->first('min_months')"
                        hint="Mínimo de meses de contrato requeridos"
                    />
                </div>
            </x-card>

            <!-- Sección 3: Vigencia y Límites -->
            <x-card title="Vigencia y Límites" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="date"
                        name="valid_from"
                        label="Válida Desde"
                        :value="old('valid_from', $promotion->valid_from?->format('Y-m-d'))"
                        :error="$errors->first('valid_from')"
                    />

                    <x-input
                        type="date"
                        name="valid_until"
                        label="Válida Hasta"
                        :value="old('valid_until', $promotion->valid_until?->format('Y-m-d'))"
                        :error="$errors->first('valid_until')"
                    />

                    <x-input
                        type="number"
                        name="max_uses"
                        label="Usos Máximos"
                        :value="old('max_uses', $promotion->max_uses)"
                        :error="$errors->first('max_uses')"
                        hint="Número máximo de veces que se puede usar"
                    />

                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">
                            Usos Actuales
                        </label>
                        <p class="text-2xl font-semibold text-secondary-900">{{ $promotion->current_uses }}</p>
                    </div>
                </div>
            </x-card>

            <!-- Sección 4: Estado -->
            <x-card title="Estado" class="mb-6">
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}
                               class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Promoción Activa</span>
                    </label>
                    <p class="mt-1 text-xs text-secondary-500">Solo las promociones activas se pueden aplicar a nuevos contratos</p>
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('catalog.promotions.show', $promotion) }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
