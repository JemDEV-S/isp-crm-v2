@extends('layouts.app')

@section('title', 'Dispositivos')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Dispositivos</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Dispositivos</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona los dispositivos de red del sistema</p>
            </div>
            @can('network.device.create')
                <a href="{{ route('network.devices.create') }}">
                    <x-button icon="plus">
                        Nuevo Dispositivo
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- filters -->
        <x-card>
            <form method="GET" action="{{ route('network.devices.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre, IP o modelo..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="status" label="Estado" placeholder="Todos los estados">
                        @foreach (\Modules\Network\Enums\DeviceStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('network.devices.index') }}">
                        <x-button type="button" variant="ghost">
                            Limpiar
                        </x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>
        <!-- Table -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Nodo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Tipo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Modelo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Dirección IP
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse ($devices as $device)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium">{{ $device->node->name}}</div>
                            <div class="text-xs text-secondary-500">{{ $device->node->address }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $device->type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $device->brand }} {{ $device->model }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-mono text-sm">
                            {{ $device->ip_address }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $device->status->label() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('network.devices.show', $device) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('network.device.update')
                                    <a href="{{ route('network.devices.edit', $device) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('network.device.delete')
                                    <form action="{{ route('network.devices.destroy', $device) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este usuario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger-600 hover:text-danger-900">
                                            <x-icon name="trash" class="w-5 h-5" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="users" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay Dispositivos</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo Dispositivo.</p>
                                @can('network.device.create')
                                    <div class="mt-4">
                                        <a href="{{ route('network.devices.create') }}">
                                            <x-button icon="plus">Nuevo Dispositivo de Red</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>
            @if ($devices->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200 bg-secondary-50">
                    {{ $devices->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
