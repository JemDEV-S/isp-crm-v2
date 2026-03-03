@extends('layouts.app')

@section('title', 'Crear Plan')

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('catalog.plans.index') }}" class="text-secondary-500 hover:text-secondary-700">Planes</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Crear Plan</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Plan de Servicio</h1>
            <p class="mt-1 text-sm text-secondary-500">Complete la información para crear un nuevo plan</p>
        </div>

        <!-- Formulario -->
        <form action="{{ route('catalog.plans.store') }}" method="POST">
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
                        placeholder="PLAN-100M"
                        hint="Código único del plan"
                    />

                    <x-input
                        name="name"
                        label="Nombre"
                        :value="old('name')"
                        :error="$errors->first('name')"
                        required
                        placeholder="Internet 100 Mbps"
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
                            placeholder="Descripción detallada del plan..."
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <!-- Sección 2: Características Técnicas -->
            <x-card title="Características Técnicas" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-select
                        name="technology"
                        label="Tecnología"
                        :error="$errors->first('technology')"
                        required
                    >
                        <option value="">Seleccione una tecnología</option>
                        @foreach($technologies as $value => $label)
                            <option value="{{ $value }}" {{ old('technology') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-input
                        type="number"
                        name="priority"
                        label="Prioridad"
                        :value="old('priority', 5)"
                        :error="$errors->first('priority')"
                        min="1"
                        max="8"
                        hint="Prioridad de queue (1-8)"
                    />

                    <x-input
                        type="number"
                        name="download_speed"
                        label="Velocidad de Bajada (Mbps)"
                        :value="old('download_speed')"
                        :error="$errors->first('download_speed')"
                        required
                        placeholder="100"
                    />

                    <x-input
                        type="number"
                        name="upload_speed"
                        label="Velocidad de Subida (Mbps)"
                        :value="old('upload_speed')"
                        :error="$errors->first('upload_speed')"
                        required
                        placeholder="50"
                    />

                    <div>
                        <label class="inline-flex items-center mt-7">
                            <input type="checkbox" name="burst_enabled" value="1" {{ old('burst_enabled') ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Habilitar Burst</span>
                        </label>
                        <p class="mt-1 text-xs text-secondary-500">Permite ráfagas de velocidad temporal</p>
                    </div>
                </div>
            </x-card>

            <!-- Sección 3: Precios -->
            <x-card title="Precios" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="number"
                        step="0.01"
                        name="price"
                        label="Precio Mensual (S/)"
                        :value="old('price')"
                        :error="$errors->first('price')"
                        required
                        placeholder="89.90"
                    />

                    <x-input
                        type="number"
                        step="0.01"
                        name="installation_fee"
                        label="Costo de Instalación (S/)"
                        :value="old('installation_fee', 0)"
                        :error="$errors->first('installation_fee')"
                        placeholder="150.00"
                    />
                </div>
            </x-card>

            <!-- Sección 4: Configuración de Red -->
            <x-card title="Configuración de Red" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="router_profile"
                        label="Perfil de RouterOS"
                        :value="old('router_profile')"
                        :error="$errors->first('router_profile')"
                        placeholder="PLAN-100M"
                        hint="Nombre del perfil en Mikrotik"
                    />

                    <x-input
                        name="olt_profile"
                        label="Perfil de OLT"
                        :value="old('olt_profile')"
                        :error="$errors->first('olt_profile')"
                        placeholder="PLAN-100M-GPON"
                        hint="Nombre del perfil en OLT (Huawei/ZTE)"
                    />
                </div>

                <div class="mt-4 p-4 bg-secondary-50 rounded-lg">
                    <p class="text-sm text-secondary-700">
                        <strong>Nota:</strong> Los campos de IP Pool y Device se configuran en el módulo de Network.
                        Si necesitas asignar recursos de red específicos, ve a la sección de edición después de crear el plan.
                    </p>
                </div>
            </x-card>

            <!-- Sección 5: Estado y Visibilidad -->
            <x-card title="Estado y Visibilidad" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Plan Activo</span>
                        </label>
                        <p class="mt-1 text-xs text-secondary-500">Solo los planes activos pueden asignarse a clientes</p>
                    </div>

                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_visible" value="1" {{ old('is_visible', true) ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Visible en Catálogo Público</span>
                        </label>
                        <p class="mt-1 text-xs text-secondary-500">Mostrar este plan en el catálogo público</p>
                    </div>
                </div>
            </x-card>

            <!-- Sección 6: Promociones y Addons -->
            <x-card title="Promociones y Addons" class="mb-6">
                <div class="space-y-6">
                    <!-- Promociones -->
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-2">
                            Promociones Disponibles
                        </label>
                        <div class="space-y-2 max-h-48 overflow-y-auto border border-secondary-200 rounded-lg p-3">
                            @forelse($promotions as $promotion)
                                <label class="flex items-start">
                                    <input
                                        type="checkbox"
                                        name="promotions[]"
                                        value="{{ $promotion->id }}"
                                        {{ in_array($promotion->id, old('promotions', [])) ? 'checked' : '' }}
                                        class="mt-1 rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    >
                                    <div class="ml-2">
                                        <div class="text-sm font-medium text-secondary-900">{{ $promotion->name }}</div>
                                        <div class="text-xs text-secondary-500">
                                            {{ $promotion->discount_type->label() }}:
                                            {{ $promotion->discount_type->value === 'percentage' ? $promotion->discount_value . '%' : 'S/ ' . $promotion->discount_value }}
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <p class="text-sm text-secondary-500">No hay promociones disponibles</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Addons -->
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-2">
                            Addons Compatibles
                        </label>
                        <div class="space-y-2 max-h-48 overflow-y-auto border border-secondary-200 rounded-lg p-3">
                            @forelse($addons as $addon)
                                <label class="flex items-start">
                                    <input
                                        type="checkbox"
                                        name="addons[]"
                                        value="{{ $addon->id }}"
                                        {{ in_array($addon->id, old('addons', [])) ? 'checked' : '' }}
                                        class="mt-1 rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    >
                                    <div class="ml-2">
                                        <div class="text-sm font-medium text-secondary-900">{{ $addon->name }}</div>
                                        <div class="text-xs text-secondary-500">
                                            S/ {{ number_format($addon->price, 2) }}
                                            {{ $addon->is_recurring ? '(mensual)' : '(único)' }}
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <p class="text-sm text-secondary-500">No hay addons disponibles</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('catalog.plans.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Crear Plan</x-button>
            </div>
        </form>
    </div>
@endsection
