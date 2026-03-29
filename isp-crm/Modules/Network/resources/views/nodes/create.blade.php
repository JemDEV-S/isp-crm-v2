@extends('layouts.app')

@section('title', 'Registrar Nuevo Nodo')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.nodes.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Nodos
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Nuevo Nodo</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Registrar Nuevo Nodo</h1>
            <p class="mt-1 text-sm text-secondary-500">
                Complete la información para dar de alta una nueva ubicación de infraestructura (Torre, Data Center, etc.).
            </p>
        </div>

        <form action="{{ route('network.nodes.store') }}" method="POST">
            @csrf

            <x-card title="Información Principal" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="name"
                        label="Nombre del Nodo"
                        placeholder="Ej: Torre Central - Miraflores"
                        :value="old('name')"
                        :error="$errors->first('name')"
                        required
                        icon="tag"
                    />

                    <x-input
                        name="code"
                        label="Código Identificador"
                        placeholder="Ej: NOD-001"
                        :value="old('code')"
                        :error="$errors->first('code')"
                        required
                        hint="Debe ser único en el sistema."
                        icon="qrcode"
                    />

                    <x-select
                        name="type"
                        label="Tipo de Infraestructura"
                        placeholder="Seleccione tipo..."
                        :error="$errors->first('type')"
                        required
                    >
                        @foreach(\Modules\Network\Enums\NodeType::cases() as $type)
                            <option value="{{ $type->value }}" {{ old('type') == $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select
                        name="status"
                        label="Estado Operativo"
                        :error="$errors->first('status')"
                        required
                    >
                        @foreach(\Modules\Network\Enums\NodeStatus::cases() as $status)
                            <option value="{{ $status->value }}"
                                {{ old('status') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
            </x-card>

            <x-card title="Ubicación Geográfica" class="mb-6">
                <div class="space-y-6">
                    <x-input
                        name="address"
                        label="Dirección Física"
                        placeholder="Av. Principal 123, Distrito..."
                        :value="old('address')"
                        :error="$errors->first('address')"
                        required
                        icon="location-marker"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-input
                            name="altitude"
                            label="Altitud (msnm)"
                            type="number"
                            placeholder="2300"
                            :value="old('altitude')"
                            :error="$errors->first('altitude')"
                        />
                    </div>

                    <x-geo-point-picker
                        latitude-name="latitude"
                        longitude-name="longitude"
                        :latitude-value="old('latitude')"
                        :longitude-value="old('longitude')"
                        :latitude-error="$errors->first('latitude')"
                        :longitude-error="$errors->first('longitude')"
                        help="Haz clic sobre el mapa para registrar la ubicacion del nodo o usa tu ubicacion actual."
                        height="20rem"
                    />
                </div>
            </x-card>

            <x-card title="Detalles Adicionales" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="date"
                        name="commissioned_at"
                        label="Fecha de Puesta en Marcha"
                        :value="old('commissioned_at', now()->format('Y-m-d'))"
                        :error="$errors->first('commissioned_at')"
                    />

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Observaciones / Descripción
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Detalles técnicos adicionales sobre el acceso o llaves..."
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('network.nodes.index') }}">
                    <x-button variant="ghost" type="button">
                        Cancelar
                    </x-button>
                </a>
                <x-button type="submit" variant="primary" icon="check">
                    Guardar Nodo
                </x-button>
            </div>
        </form>
    </div>
@endsection
