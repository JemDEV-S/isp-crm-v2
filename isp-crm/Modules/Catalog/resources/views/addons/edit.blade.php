@extends('layouts.app')

@section('title', 'Editar Addon')

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('catalog.addons.index') }}" class="text-secondary-500 hover:text-secondary-700">Addons</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Editar: {{ $addon->name }}</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Addon: {{ $addon->name }}</h1>
            <p class="mt-1 text-sm text-secondary-500">Modifique la información del addon</p>
        </div>

        <!-- Formulario -->
        <form action="{{ route('catalog.addons.update', $addon) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Sección 1: Información Básica -->
            <x-card title="Información Básica" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="code"
                        label="Código"
                        :value="old('code', $addon->code)"
                        :error="$errors->first('code')"
                        required
                        hint="Código único del addon"
                    />

                    <x-input
                        name="name"
                        label="Nombre"
                        :value="old('name', $addon->name)"
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
                        >{{ old('description', $addon->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <!-- Sección 2: Precio y Configuración -->
            <x-card title="Precio y Configuración" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="number"
                        step="0.01"
                        name="price"
                        label="Precio (S/)"
                        :value="old('price', $addon->price)"
                        :error="$errors->first('price')"
                        required
                    />

                    <div>
                        <label class="inline-flex items-center mt-7">
                            <input type="checkbox" name="is_recurring" value="1" {{ old('is_recurring', $addon->is_recurring) ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Cargo Recurrente</span>
                        </label>
                        <p class="mt-1 text-xs text-secondary-500">Se cobrará mensualmente junto al plan</p>
                    </div>
                </div>
            </x-card>

            <!-- Sección 3: Estado -->
            <x-card title="Estado" class="mb-6">
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $addon->is_active) ? 'checked' : '' }}
                               class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Addon Activo</span>
                    </label>
                    <p class="mt-1 text-xs text-secondary-500">Solo los addons activos se pueden agregar a las suscripciones</p>
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('catalog.addons.show', $addon) }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
