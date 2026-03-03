@extends('layouts.app')

@section('title', 'Editar Dispositivo')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.devices.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Dispositivos
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Editar Dispositivo</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Dispositivo: {{ $device->brand }} {{ $device->model }}</h1>
            <p class="mt-1 text-sm text-secondary-500">
                Modifique la información del dispositivo de red.
            </p>
        </div>

        <form action="{{ route('network.devices.update', $device) }}" method="POST">
            @csrf
            @method('PUT')

            <x-card title="Información Principal" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-select
                        name="node_id"
                        label="Nodo / Ubicación"
                        placeholder="Seleccione el nodo..."
                        :error="$errors->first('node_id')"
                        required
                    >
                        @foreach($nodes as $node)
                            <option value="{{ $node->id }}" {{ old('node_id', $device->node_id) == $node->id ? 'selected' : '' }}>
                                {{ $node->code }} - {{ $node->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select
                        name="type"
                        label="Tipo de Dispositivo"
                        placeholder="Seleccione tipo..."
                        :error="$errors->first('type')"
                        required
                    >
                        @foreach($deviceTypes as $type)
                            <option value="{{ $type->value }}" {{ old('type', $device->type->value) == $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-input
                        name="brand"
                        label="Marca"
                        placeholder="Ej: MikroTik, Huawei, Cisco"
                        :value="old('brand', $device->brand)"
                        :error="$errors->first('brand')"
                        required
                    />

                    <x-input
                        name="model"
                        label="Modelo"
                        placeholder="Ej: RB1100AHx4, MA5800-X7"
                        :value="old('model', $device->model)"
                        :error="$errors->first('model')"
                        required
                    />

                    <x-input
                        name="serial_number"
                        label="Número de Serie"
                        placeholder="S/N del dispositivo"
                        :value="old('serial_number', $device->serial_number)"
                        :error="$errors->first('serial_number')"
                        hint="Opcional pero recomendado"
                    />

                    <x-select
                        name="status"
                        label="Estado Operativo"
                        :error="$errors->first('status')"
                        required
                    >
                        @foreach(\Modules\Network\Enums\DeviceStatus::cases() as $status)
                            <option value="{{ $status->value }}"
                                {{ old('status', $device->status->value) == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
            </x-card>

            <x-card title="Configuración de Red" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="ip_address"
                        label="Dirección IP"
                        placeholder="192.168.1.1"
                        :value="old('ip_address', $device->ip_address)"
                        :error="$errors->first('ip_address')"
                        hint="IP de gestión"
                        icon="globe"
                    />

                    <x-input
                        name="mac_address"
                        label="Dirección MAC"
                        placeholder="00:11:22:33:44:55"
                        :value="old('mac_address', $device->mac_address)"
                        :error="$errors->first('mac_address')"
                    />

                    <x-input
                        name="firmware_version"
                        label="Versión de Firmware"
                        placeholder="Ej: 7.10.2"
                        :value="old('firmware_version', $device->firmware_version)"
                        :error="$errors->first('firmware_version')"
                    />

                    <x-input
                        name="snmp_community"
                        label="SNMP Community"
                        placeholder="public"
                        :value="old('snmp_community', $device->snmp_community)"
                        :error="$errors->first('snmp_community')"
                        hint="Para monitoreo SNMP"
                    />
                </div>
            </x-card>

            <x-card title="Credenciales API (Opcional)" class="mb-6">
                <div class="space-y-4">
                    <div class="bg-info-50 border border-info-200 rounded-lg p-4">
                        <div class="flex gap-3">
                            <x-icon name="information-circle" class="w-5 h-5 text-info-600 flex-shrink-0" />
                            <div class="text-sm text-info-700">
                                <p class="font-medium">Configuración para gestión automática</p>
                                <p class="mt-1">Complete estos datos si desea habilitar la gestión automática del dispositivo (RouterOS API, OLT API, etc.).</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-input
                            type="number"
                            name="api_port"
                            label="Puerto API"
                            placeholder="8728"
                            :value="old('api_port', $device->api_port)"
                            :error="$errors->first('api_port')"
                            hint="Ej: 8728 para RouterOS"
                        />

                        <x-input
                            name="api_user"
                            label="Usuario API"
                            placeholder="admin"
                            :value="old('api_user', $device->api_user)"
                            :error="$errors->first('api_user')"
                        />

                        <x-input
                            type="password"
                            name="api_password"
                            label="Contraseña API"
                            placeholder="••••••••"
                            :value="old('api_password')"
                            :error="$errors->first('api_password')"
                            hint="Deje en blanco para mantener la actual"
                        />
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
                        placeholder="Detalles adicionales sobre el dispositivo, ubicación física específica, etc."
                    >{{ old('notes', $device->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('network.devices.show', $device) }}">
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
