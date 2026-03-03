@extends('layouts.app')

@section('title', 'Crear Promoción')

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('catalog.promotions.index') }}" class="text-secondary-500 hover:text-secondary-700">Promociones</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Crear Promoción</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Promoción</h1>
            <p class="mt-1 text-sm text-secondary-500">Complete la información para crear una nueva promoción</p>
        </div>

        <!-- Formulario -->
        <form action="{{ route('catalog.promotions.store') }}" method="POST">
            @csrf

            <!-- Sección 1: Información Básica -->
            <x-card title="Información Básica" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="code"
                        label="Código"
                        :value="old('code')"
                        :error="$errors->first('code')"
                        required
                        placeholder="PROMO2024"
                        hint="Código único de la promoción"
                    />

                    <x-input
                        name="name"
                        label="Nombre"
                        :value="old('name')"
                        :error="$errors->first('name')"
                        required
                        placeholder="Descuento Verano 2024"
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
                            placeholder="Descripción de la promoción..."
                        >{{ old('description') }}</textarea>
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
                        <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                        <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Monto Fijo</option>
                    </x-select>

                    <x-input
                        type="number"
                        step="0.01"
                        name="discount_value"
                        label="Valor del Descuento"
                        :value="old('discount_value')"
                        :error="$errors->first('discount_value')"
                        required
                        placeholder="10 o 15.00"
                        hint="% si es porcentaje, S/ si es monto fijo"
                    />

                    <x-select
                        name="applies_to"
                        label="Aplicable a"
                        :error="$errors->first('applies_to')"
                        required
                    >
                        <option value="">Seleccione dónde aplicar</option>
                        <option value="monthly" {{ old('applies_to') == 'monthly' ? 'selected' : '' }}>Precio Mensual</option>
                        <option value="installation" {{ old('applies_to') == 'installation' ? 'selected' : '' }}>Instalación</option>
                        <option value="both" {{ old('applies_to') == 'both' ? 'selected' : '' }}>Ambos</option>
                    </x-select>

                    <x-input
                        type="number"
                        name="min_months"
                        label="Meses Mínimos"
                        :value="old('min_months', 0)"
                        :error="$errors->first('min_months')"
                        placeholder="0"
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
                        :value="old('valid_from')"
                        :error="$errors->first('valid_from')"
                    />

                    <x-input
                        type="date"
                        name="valid_until"
                        label="Válida Hasta"
                        :value="old('valid_until')"
                        :error="$errors->first('valid_until')"
                    />

                    <x-input
                        type="number"
                        name="max_uses"
                        label="Usos Máximos"
                        :value="old('max_uses')"
                        :error="$errors->first('max_uses')"
                        placeholder="Dejar vacío para ilimitado"
                        hint="Número máximo de veces que se puede usar"
                    />
                </div>
            </x-card>

            <!-- Sección 4: Estado -->
            <x-card title="Estado" class="mb-6">
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Promoción Activa</span>
                    </label>
                    <p class="mt-1 text-xs text-secondary-500">Solo las promociones activas se pueden aplicar a nuevos contratos</p>
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('catalog.promotions.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Crear Promoción</x-button>
            </div>
        </form>
    </div>
@endsection
