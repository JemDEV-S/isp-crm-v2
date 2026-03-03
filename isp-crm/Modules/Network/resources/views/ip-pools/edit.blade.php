@extends('layouts.app')

@section('title', 'Editar Pool de IP')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.ip-pools.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Pools de IP
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Editar</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Pool: {{ $pool->name }}</h1>
            <p class="mt-1 text-sm text-secondary-500">
                Modifique la configuración del pool de direcciones IP.
            </p>
        </div>

        <form action="{{ route('network.ip-pools.update', $pool) }}" method="POST">
            @csrf
            @method('PUT')

            <x-card title="Información Principal" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input
                            name="name"
                            label="Nombre del Pool"
                            placeholder="Ej: Pool Residencial - Zona Norte"
                            :value="old('name', $pool->name)"
                            :error="$errors->first('name')"
                            required
                            icon="tag"
                        />
                    </div>

                    <x-select
                        name="device_id"
                        label="Dispositivo Asociado"
                        placeholder="Seleccione dispositivo..."
                        :error="$errors->first('device_id')"
                    >
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" {{ old('device_id', $pool->device_id) == $device->id ? 'selected' : '' }}>
                                {{ $device->brand }} {{ $device->model }} - {{ $device->node->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-input
                        type="number"
                        name="vlan_id"
                        label="VLAN ID (Opcional)"
                        placeholder="100"
                        :value="old('vlan_id', $pool->vlan_id)"
                        :error="$errors->first('vlan_id')"
                    />
                </div>
            </x-card>

            <x-card title="Configuración DNS" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="dns_primary"
                        label="DNS Primario"
                        placeholder="8.8.8.8"
                        :value="old('dns_primary', $pool->dns_primary)"
                        :error="$errors->first('dns_primary')"
                    />

                    <x-input
                        name="dns_secondary"
                        label="DNS Secundario"
                        placeholder="8.8.4.4"
                        :value="old('dns_secondary', $pool->dns_secondary)"
                        :error="$errors->first('dns_secondary')"
                    />
                </div>
            </x-card>

            <x-card title="Configuración Adicional" class="mb-6">
                <div class="space-y-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $pool->is_active) ? 'checked' : '' }}
                               class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Pool activo</span>
                    </label>

                    <div>
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Descripción
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Detalles adicionales sobre el uso de este pool..."
                        >{{ old('description', $pool->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('network.ip-pools.show', $pool) }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" variant="primary" icon="check">
                    Guardar Cambios
                </x-button>
            </div>
        </form>
    </div>
@endsection
