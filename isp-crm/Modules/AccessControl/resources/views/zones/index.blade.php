@extends('layouts.app')

@section('title', 'Zonas')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Zonas</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Zonas</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona las zonas geográficas del sistema</p>
            </div>
            @can('create', \Modules\AccessControl\Entities\Zone::class)
                <a href="{{ route('accesscontrol.zones.create') }}">
                    <x-button icon="plus">
                        Nueva Zona
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('accesscontrol.zones.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre o código..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />
                </div>

                <div class="w-48">
                    <x-select name="parent_id" label="Zona Padre" placeholder="Todas">
                        <option value="root" {{ ($filters['parent_id'] ?? '') === 'root' ? 'selected' : '' }}>Solo raíces</option>
                        @foreach($parentZones as $zone)
                            <option value="{{ $zone->id }}" {{ ($filters['parent_id'] ?? '') == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="w-40">
                    <x-select name="is_active" label="Estado" placeholder="Todos">
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Activas</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactivas</option>
                    </x-select>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('accesscontrol.zones.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
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
                        Zona
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Código
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Zona Padre
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Sub-zonas
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Usuarios
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($zones as $zone)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-lg bg-success-100 flex items-center justify-center">
                                        <x-icon name="map-pin" class="w-5 h-5 text-success-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-secondary-900">{{ $zone->name }}</div>
                                    @if($zone->description)
                                        <div class="text-xs text-secondary-500">{{ Str::limit($zone->description, 40) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono text-secondary-600">{{ $zone->code }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                            {{ $zone->parent?->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge variant="default">{{ $zone->children_count }}</x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge variant="info">{{ $zone->users_count }}</x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$zone->is_active ? 'success' : 'danger'" dot>
                                {{ $zone->is_active ? 'Activa' : 'Inactiva' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('accesscontrol.zones.show', $zone) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('update', $zone)
                                    <a href="{{ route('accesscontrol.zones.edit', $zone) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('delete', $zone)
                                    @if($zone->children_count == 0 && $zone->users_count == 0)
                                        <form action="{{ route('accesscontrol.zones.destroy', $zone) }}" method="POST" class="inline"
                                              onsubmit="return confirm('¿Estás seguro de eliminar esta zona?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-danger-600 hover:text-danger-900">
                                                <x-icon name="trash" class="w-5 h-5" />
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="map" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay zonas</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando una nueva zona.</p>
                                @can('create', \Modules\AccessControl\Entities\Zone::class)
                                    <div class="mt-4">
                                        <a href="{{ route('accesscontrol.zones.create') }}">
                                            <x-button icon="plus">Nueva Zona</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if($zones->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $zones->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
