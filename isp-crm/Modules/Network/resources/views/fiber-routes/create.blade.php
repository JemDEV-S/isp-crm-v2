@extends('layouts.app')

@section('title', 'Crear Ruta de Fibra')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.fiber-routes.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Rutas de Fibra
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Nueva Ruta</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Nueva Ruta de Fibra</h1>
            <p class="mt-1 text-sm text-secondary-500">
                Registre una conexión de fibra óptica entre dos nodos de la red.
            </p>
        </div>

        <form action="{{ route('network.fiber-routes.store') }}" method="POST">
            @csrf

            <x-card title="Nodos de la Ruta" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-select
                        name="from_node_id"
                        label="Nodo de Origen"
                        placeholder="Seleccione nodo origen..."
                        :error="$errors->first('from_node_id')"
                        required
                    >
                        @foreach($nodes as $node)
                            <option value="{{ $node->id }}" {{ old('from_node_id', $selectedFromNodeId ?? '') == $node->id ? 'selected' : '' }}>
                                {{ $node->code }} - {{ $node->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select
                        name="to_node_id"
                        label="Nodo de Destino"
                        placeholder="Seleccione nodo destino..."
                        :error="$errors->first('to_node_id')"
                        required
                    >
                        @foreach($nodes as $node)
                            <option value="{{ $node->id }}" {{ old('to_node_id', $selectedToNodeId ?? '') == $node->id ? 'selected' : '' }}>
                                {{ $node->code }} - {{ $node->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="mt-4 p-4 bg-info-50 border border-info-200 rounded-lg">
                    <div class="flex gap-3">
                        <x-icon name="information-circle" class="w-5 h-5 text-info-600 flex-shrink-0" />
                        <div class="text-sm text-info-700">
                            <p class="font-medium">Conexión bidireccional</p>
                            <p class="mt-1">La ruta de fibra conecta ambos nodos en ambas direcciones. No es necesario crear rutas separadas para cada sentido.</p>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Características de la Ruta" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="number"
                        name="distance_meters"
                        label="Distancia (metros)"
                        placeholder="1500"
                        :value="old('distance_meters')"
                        :error="$errors->first('distance_meters')"
                        hint="Distancia aproximada del tendido"
                    />

                    <x-input
                        type="number"
                        name="fiber_count"
                        label="Número de Hilos de Fibra"
                        placeholder="24"
                        :value="old('fiber_count')"
                        :error="$errors->first('fiber_count')"
                        hint="Total de hilos en el cable"
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
                </div>
            </x-card>

            <x-card title="Observaciones" class="mb-6">
                <div>
                    <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                        Notas / Comentarios
                    </label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="3"
                        class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Detalles sobre el tendido, ductos utilizados, puntos de empalme, etc."
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('network.fiber-routes.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" variant="primary" icon="check">
                    Guardar Ruta
                </x-button>
            </div>
        </form>
    </div>
@endsection
