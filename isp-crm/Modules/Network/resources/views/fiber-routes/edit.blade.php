@extends('layouts.app')

@section('title', 'Editar Ruta de Fibra')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.fiber-routes.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Rutas de Fibra
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Editar</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Ruta de Fibra</h1>
            <p class="mt-1 text-sm text-secondary-500">
                {{ $fiberRoute->fromNode->name }} → {{ $fiberRoute->toNode->name }}
            </p>
        </div>

        <form action="{{ route('network.fiber-routes.update', $fiberRoute) }}" method="POST">
            @csrf
            @method('PUT')

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
                            <option value="{{ $node->id }}" {{ old('from_node_id', $fiberRoute->from_node_id) == $node->id ? 'selected' : '' }}>
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
                            <option value="{{ $node->id }}" {{ old('to_node_id', $fiberRoute->to_node_id) == $node->id ? 'selected' : '' }}>
                                {{ $node->code }} - {{ $node->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
            </x-card>

            <x-card title="Características de la Ruta" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="number"
                        name="distance_meters"
                        label="Distancia (metros)"
                        placeholder="1500"
                        :value="old('distance_meters', $fiberRoute->distance_meters)"
                        :error="$errors->first('distance_meters')"
                        hint="Distancia aproximada del tendido"
                    />

                    <x-input
                        type="number"
                        name="fiber_count"
                        label="Número de Hilos de Fibra"
                        placeholder="24"
                        :value="old('fiber_count', $fiberRoute->fiber_count)"
                        :error="$errors->first('fiber_count')"
                        hint="Total de hilos en el cable"
                    />

                    <x-select
                        name="status"
                        label="Estado"
                        :error="$errors->first('status')"
                        required
                    >
                        <option value="active" {{ old('status', $fiberRoute->status) == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status', $fiberRoute->status) == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="maintenance" {{ old('status', $fiberRoute->status) == 'maintenance' ? 'selected' : '' }}>Mantenimiento</option>
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
                    >{{ old('notes', $fiberRoute->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('network.fiber-routes.show', $fiberRoute) }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" variant="primary" icon="check">
                    Guardar Cambios
                </x-button>
            </div>
        </form>
    </div>
@endsection
