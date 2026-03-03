@extends('layouts.app')

@section('title', 'Editar Nodo')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.nodes.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Nodos
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Editar Nodo</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Nodo: {{ $node->name }}</h1>
            <p class="mt-1 text-sm text-secondary-500">
                Modifique la información del nodo de infraestructura.
            </p>
        </div>

        <form action="{{ route('network.nodes.update', $node) }}" method="POST">
            @csrf
            @method('PUT')

            <x-card title="Información Principal" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="name"
                        label="Nombre del Nodo"
                        placeholder="Ej: Torre Central - Miraflores"
                        :value="old('name', $node->name)"
                        :error="$errors->first('name')"
                        required
                        icon="tag"
                    />

                    <x-input
                        name="code"
                        label="Código Identificador"
                        placeholder="Ej: NOD-001"
                        :value="old('code', $node->code)"
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
                            <option value="{{ $type->value }}" {{ old('type', $node->type->value) == $type->value ? 'selected' : '' }}>
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
                                {{ old('status', $node->status->value) == $status->value ? 'selected' : '' }}>
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
                        :value="old('address', $node->address)"
                        :error="$errors->first('address')"
                        required
                        icon="location-marker"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-input
                            name="latitude"
                            label="Latitud"
                            placeholder="-16.4090"
                            :value="old('latitude', $node->latitude)"
                            :error="$errors->first('latitude')"
                            hint="Formato decimal"
                        />

                        <x-input
                            name="longitude"
                            label="Longitud"
                            placeholder="-71.5374"
                            :value="old('longitude', $node->longitude)"
                            :error="$errors->first('longitude')"
                            hint="Formato decimal"
                        />

                        <x-input
                            name="altitude"
                            label="Altitud (msnm)"
                            type="number"
                            placeholder="2300"
                            :value="old('altitude', $node->altitude)"
                            :error="$errors->first('altitude')"
                        />
                    </div>
                </div>
            </x-card>

            <x-card title="Detalles Adicionales" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="date"
                        name="commissioned_at"
                        label="Fecha de Puesta en Marcha"
                        :value="old('commissioned_at', $node->commissioned_at?->format('Y-m-d'))"
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
                        >{{ old('description', $node->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('network.nodes.show', $node) }}">
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
