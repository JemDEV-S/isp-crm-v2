@extends('layouts.app')

@section('title', 'Registrar Nueva Caja NAP')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.nap-boxes.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Cajas NAP
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Nueva Caja NAP</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Registrar Nueva Caja NAP</h1>
            <p class="mt-1 text-sm text-secondary-500">
                Complete la información para dar de alta una nueva caja de distribución de fibra óptica.
            </p>
        </div>

        <form action="{{ route('network.nap-boxes.store') }}" method="POST">
            @csrf

            <x-card title="Información Principal" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="code"
                        label="Código Identificador"
                        placeholder="Ej: NAP-001"
                        :value="old('code')"
                        :error="$errors->first('code')"
                        required
                        hint="Debe ser único en el sistema"
                        icon="qrcode"
                    />

                    <x-input
                        name="name"
                        label="Nombre de la Caja NAP"
                        placeholder="Ej: NAP Av. Principal"
                        :value="old('name')"
                        :error="$errors->first('name')"
                        required
                        icon="tag"
                    />

                    <x-select
                        name="node_id"
                        label="Nodo Principal"
                        placeholder="Seleccione el nodo..."
                        :error="$errors->first('node_id')"
                        required
                    >
                        @foreach($nodes as $node)
                            <option value="{{ $node->id }}" {{ old('node_id', $selectedNodeId ?? '') == $node->id ? 'selected' : '' }}>
                                {{ $node->code }} - {{ $node->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-input
                        name="type"
                        label="Tipo de Caja"
                        placeholder="Ej: splitter_1x8, splitter_1x16"
                        :value="old('type')"
                        :error="$errors->first('type')"
                        required
                        hint="Tipo de splitter o caja"
                    />

                    <x-input
                        type="number"
                        name="total_ports"
                        label="Número de Puertos"
                        placeholder="8"
                        :value="old('total_ports', 8)"
                        :error="$errors->first('total_ports')"
                        required
                        hint="Se crearán automáticamente"
                    />

                    <x-select
                        name="status"
                        label="Estado"
                        :error="$errors->first('status')"
                        required
                    >
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Mantenimiento</option>
                    </x-select>

                    <x-input
                        type="date"
                        name="installed_at"
                        label="Fecha de Instalación"
                        :value="old('installed_at', now()->format('Y-m-d'))"
                        :error="$errors->first('installed_at')"
                    />
                </div>
            </x-card>

            <x-card title="Ubicación Geográfica" class="mb-6">
                <div class="space-y-6">
                    <x-input
                        name="address"
                        label="Dirección Física"
                        placeholder="Av. Principal esquina calle secundaria..."
                        :value="old('address')"
                        :error="$errors->first('address')"
                        icon="location-marker"
                    />

                    <x-geo-point-picker
                        latitude-name="latitude"
                        longitude-name="longitude"
                        :latitude-value="old('latitude')"
                        :longitude-value="old('longitude')"
                        :latitude-error="$errors->first('latitude')"
                        :longitude-error="$errors->first('longitude')"
                        help="Haz clic sobre el mapa para ubicar la caja NAP o usa tu ubicacion actual."
                        height="20rem"
                    />

                    <div class="bg-info-50 border border-info-200 rounded-lg p-4">
                        <div class="flex gap-3">
                            <x-icon name="information-circle" class="w-5 h-5 text-info-600 flex-shrink-0" />
                            <div class="text-sm text-info-700">
                                <p class="font-medium">Coordenadas GPS</p>
                                <p class="mt-1">Puede obtener las coordenadas usando Google Maps (clic derecho > coordenadas) o un dispositivo GPS.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Observaciones Adicionales" class="mb-6">
                <div>
                    <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                        Notas / Descripción
                    </label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="3"
                        class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Detalles adicionales sobre la ubicación, acceso, etc."
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('network.nap-boxes.index') }}">
                    <x-button variant="ghost" type="button">
                        Cancelar
                    </x-button>
                </a>
                <x-button type="submit" variant="primary" icon="check">
                    Guardar Caja NAP
                </x-button>
            </div>
        </form>
    </div>
@endsection
