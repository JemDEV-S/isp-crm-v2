@extends('layouts.app')

@section('title', 'Crear Pool de IP')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.ip-pools.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Pools de IP
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Nuevo Pool</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Nuevo Pool de IP</h1>
            <p class="mt-1 text-sm text-secondary-500">
                Configure un nuevo rango de direcciones IP para asignación a clientes.
            </p>
        </div>

        <form action="{{ route('network.ip-pools.store') }}" method="POST">
            @csrf

            <x-card title="Información Principal" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input
                            name="name"
                            label="Nombre del Pool"
                            placeholder="Ej: Pool Residencial - Zona Norte"
                            :value="old('name')"
                            :error="$errors->first('name')"
                            required
                            icon="tag"
                        />
                    </div>

                    <x-input
                        name="network_cidr"
                        label="Red CIDR"
                        placeholder="10.0.0.0/24"
                        :value="old('network_cidr')"
                        :error="$errors->first('network_cidr')"
                        required
                        hint="Formato: IP/máscara (ej: 192.168.1.0/24)"
                    />

                    <x-input
                        name="gateway"
                        label="Gateway"
                        placeholder="10.0.0.1"
                        :value="old('gateway')"
                        :error="$errors->first('gateway')"
                        required
                    />

                    <x-select
                        name="type"
                        label="Tipo de Pool"
                        :error="$errors->first('type')"
                        required
                    >
                        <option value="public" {{ old('type') == 'public' ? 'selected' : '' }}>Público</option>
                        <option value="private" {{ old('type', 'private') == 'private' ? 'selected' : '' }}>Privado</option>
                        <option value="cgnat" {{ old('type') == 'cgnat' ? 'selected' : '' }}>CGNAT</option>
                    </x-select>

                    <x-select
                        name="device_id"
                        label="Dispositivo Asociado"
                        placeholder="Seleccione dispositivo..."
                        :error="$errors->first('device_id')"
                    >
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" {{ old('device_id', $selectedDeviceId ?? '') == $device->id ? 'selected' : '' }}>
                                {{ $device->brand }} {{ $device->model }} - {{ $device->node->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-input
                        type="number"
                        name="vlan_id"
                        label="VLAN ID (Opcional)"
                        placeholder="100"
                        :value="old('vlan_id')"
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
                        :value="old('dns_primary', '8.8.8.8')"
                        :error="$errors->first('dns_primary')"
                    />

                    <x-input
                        name="dns_secondary"
                        label="DNS Secundario"
                        placeholder="8.8.4.4"
                        :value="old('dns_secondary', '8.8.4.4')"
                        :error="$errors->first('dns_secondary')"
                    />
                </div>
            </x-card>

            <x-card title="Configuración Adicional" class="mb-6">
                <div class="space-y-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Pool activo</span>
                    </label>

                    <label class="inline-flex items-center">
                        <input type="checkbox" name="populate_addresses" value="1" {{ old('populate_addresses', true) ? 'checked' : '' }}
                               class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Generar direcciones IP automáticamente</span>
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
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('network.ip-pools.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" variant="primary" icon="check">
                    Guardar Pool
                </x-button>
            </div>
        </form>
    </div>
@endsection
