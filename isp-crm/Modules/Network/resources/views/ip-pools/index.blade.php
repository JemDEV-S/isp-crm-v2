@extends('layouts.app')

@section('title', 'Pools de IP')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Pools de IP</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Pools de Direcciones IP</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona los rangos de direcciones IP disponibles</p>
            </div>
            @can('network.ippool.create')
                <a href="{{ route('network.ip-pools.create') }}">
                    <x-button icon="plus">
                        Nuevo Pool de IP
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('network.ip-pools.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre o red CIDR..."
                        :value="request('search')"
                        icon="search"
                    />

                    <x-select name="type" label="Tipo" placeholder="Todos los tipos">
                        <option value="public" {{ request('type') == 'public' ? 'selected' : '' }}>Público</option>
                        <option value="private" {{ request('type') == 'private' ? 'selected' : '' }}>Privado</option>
                        <option value="cgnat" {{ request('type') == 'cgnat' ? 'selected' : '' }}>CGNAT</option>
                    </x-select>

                    <x-select name="device_id" label="Dispositivo" placeholder="Todos los dispositivos">
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" {{ request('device_id') == $device->id ? 'selected' : '' }}>
                                {{ $device->brand }} {{ $device->model }}
                            </option>
                        @endforeach
                    </x-select>

                    <div class="flex items-end">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="active_only" value="1" {{ request('active_only') ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Solo activos</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('network.ip-pools.index') }}">
                        <x-button type="button" variant="ghost">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Tabla -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Red CIDR
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Tipo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Dispositivo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Uso
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($pools as $pool)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-secondary-900">{{ $pool->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-mono text-sm">
                            {{ $pool->network_cidr }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$pool->type === 'public' ? 'info' : 'secondary'" size="sm">
                                {{ strtoupper($pool->type) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($pool->device)
                                <div class="text-sm text-secondary-900">{{ $pool->device->brand }} {{ $pool->device->model }}</div>
                            @else
                                <span class="text-sm text-secondary-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="text-sm">
                                    <span class="font-medium text-success-600">{{ $pool->free_count ?? 0 }}</span>
                                    / {{ $pool->ip_addresses_count ?? 0 }}
                                </div>
                                @if($pool->ip_addresses_count > 0)
                                    <div class="w-20 bg-secondary-200 rounded-full h-2">
                                        <div class="bg-warning-500 h-2 rounded-full"
                                             style="width: {{ (($pool->assigned_count ?? 0) / $pool->ip_addresses_count) * 100 }}%">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$pool->is_active ? 'success' : 'danger'" dot>
                                {{ $pool->is_active ? 'Activo' : 'Inactivo' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('network.ip-pools.show', $pool) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('network.ippool.update')
                                    <a href="{{ route('network.ip-pools.edit', $pool) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('network.ippool.delete')
                                    <form action="{{ route('network.ip-pools.destroy', $pool) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este pool?')">
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
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="inbox" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay pools de IP</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo rango de direcciones IP.</p>
                                @can('network.ippool.create')
                                    <div class="mt-4">
                                        <a href="{{ route('network.ip-pools.create') }}">
                                            <x-button icon="plus">Nuevo Pool de IP</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if($pools->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $pools->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
