@extends('layouts.app')

@section('title', 'Editar Caja NAP')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.nap-boxes.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Cajas NAP
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Editar</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Caja NAP: {{ $napBox->name }}</h1>
            <p class="mt-1 text-sm text-secondary-500">
                Modifique la información de la caja de distribución.
            </p>
        </div>

        <form action="{{ route('network.nap-boxes.update', $napBox) }}" method="POST">
            @csrf
            @method('PUT')

            <x-card title="Información Principal" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="name"
                        label="Nombre de la Caja NAP"
                        placeholder="Ej: NAP Av. Principal"
                        :value="old('name', $napBox->name)"
                        :error="$errors->first('name')"
                        required
                        icon="tag"
                    />

                    <x-select
                        name="status"
                        label="Estado"
                        :error="$errors->first('status')"
                        required
                    >
                        <option value="active" {{ old('status', $napBox->status) == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status', $napBox->status) == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="maintenance" {{ old('status', $napBox->status) == 'maintenance' ? 'selected' : '' }}>Mantenimiento</option>
                    </x-select>
                </div>
            </x-card>

            <x-card title="Ubicación Geográfica" class="mb-6">
                <div class="space-y-6">
                    <x-input
                        name="address"
                        label="Dirección Física"
                        placeholder="Av. Principal esquina calle secundaria..."
                        :value="old('address', $napBox->address)"
                        :error="$errors->first('address')"
                        icon="location-marker"
                    />

                    <x-geo-point-picker
                        latitude-name="latitude"
                        longitude-name="longitude"
                        :latitude-value="old('latitude', $napBox->latitude)"
                        :longitude-value="old('longitude', $napBox->longitude)"
                        :latitude-error="$errors->first('latitude')"
                        :longitude-error="$errors->first('longitude')"
                        help="Ajusta la ubicacion exacta de la NAP desde el mapa o escribiendo las coordenadas."
                        height="20rem"
                    />
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('network.nap-boxes.show', $napBox) }}">
                    <x-button variant="ghost" type="button">
                        Cancelar
                    </x-button>
                </a>
                <x-button type="submit" variant="primary" icon="check">
                    Guardar Cambios
                </x-button>
            </div>
        </form>
    </div>
@endsection
