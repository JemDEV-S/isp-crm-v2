@extends('layouts.app')

@section('title', 'Crear Zona')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('accesscontrol.zones.index') }}" class="text-secondary-500 hover:text-secondary-700">Zonas</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Crear Zona</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Zona</h1>
            <p class="mt-1 text-sm text-secondary-500">Configure una nueva zona geográfica</p>
        </div>

        <form action="{{ route('accesscontrol.zones.store') }}" method="POST">
            @csrf

            <x-card title="Información de la Zona" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="code"
                        label="Código"
                        :value="old('code')"
                        :error="$errors->first('code')"
                        required
                        placeholder="Ej: ZONA-NORTE"
                        hint="Solo letras mayúsculas, números, guiones y guiones bajos"
                    />

                    <x-input
                        name="name"
                        label="Nombre"
                        :value="old('name')"
                        :error="$errors->first('name')"
                        required
                        placeholder="Ej: Zona Norte"
                    />

                    <x-select
                        name="parent_id"
                        label="Zona Padre"
                        :error="$errors->first('parent_id')"
                        placeholder="Ninguna (Zona raíz)"
                    >
                        @foreach($parentZones as $zone)
                            <option value="{{ $zone->id }}" {{ old('parent_id') == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <div class="flex items-center">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Zona activa</span>
                        </label>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Descripción
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            class="block w-full rounded-lg border-secondary-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-secondary-900"
                            placeholder="Describe la zona geográfica..."
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <x-card title="Área Geográfica" subtitle="Opcional - Define el polígono de cobertura">
                <div class="bg-secondary-100 rounded-lg p-8 text-center">
                    <x-icon name="map" class="w-12 h-12 text-secondary-400 mx-auto" />
                    <p class="mt-2 text-sm text-secondary-500">
                        La funcionalidad de mapa estará disponible próximamente
                    </p>
                    <p class="text-xs text-secondary-400 mt-1">
                        Podrás dibujar el polígono de cobertura de la zona
                    </p>
                </div>
            </x-card>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('accesscontrol.zones.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Crear Zona</x-button>
            </div>
        </form>
    </div>
@endsection
