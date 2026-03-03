@extends('layouts.app')

@section('title', 'Rutas de Fibra')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Rutas de Fibra</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Rutas de Fibra Óptica</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona las conexiones de fibra entre nodos</p>
            </div>
            @can('network.fiberroute.create')
                <a href="{{ route('network.fiber-routes.create') }}">
                    <x-button icon="plus">
                        Nueva Ruta
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('network.fiber-routes.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nodo origen o destino..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="from_node_id" label="Nodo Origen" placeholder="Todos">
                        @foreach($nodes as $node)
                            <option value="{{ $node->id }}" {{ ($filters['from_node_id'] ?? '') == $node->id ? 'selected' : '' }}>
                                {{ $node->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="to_node_id" label="Nodo Destino" placeholder="Todos">
                        @foreach($nodes as $node)
                            <option value="{{ $node->id }}" {{ ($filters['to_node_id'] ?? '') == $node->id ? 'selected' : '' }}>
                                {{ $node->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="status" label="Estado" placeholder="Todos">
                        <option value="active" {{ ($filters['status'] ?? '') == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="maintenance" {{ ($filters['status'] ?? '') == 'maintenance' ? 'selected' : '' }}>Mantenimiento</option>
                    </x-select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('network.fiber-routes.index') }}">
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
                        Nodo Origen
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        →
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Nodo Destino
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Distancia
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Fibras
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($routes as $route)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                    <x-icon name="network" class="w-4 h-4 text-primary-600" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-secondary-900">{{ $route->fromNode->name }}</div>
                                    <div class="text-xs text-secondary-500">{{ $route->fromNode->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <x-icon name="arrow-right" class="w-5 h-5 text-secondary-400 mx-auto" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-success-100 flex items-center justify-center">
                                    <x-icon name="network" class="w-4 h-4 text-success-600" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-secondary-900">{{ $route->toNode->name }}</div>
                                    <div class="text-xs text-secondary-500">{{ $route->toNode->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($route->distance_meters)
                                <div class="text-sm text-secondary-900">
                                    <span class="font-semibold">{{ $route->distance_km }}</span> km
                                </div>
                                <div class="text-xs text-secondary-500">{{ number_format($route->distance_meters) }} m</div>
                            @else
                                <span class="text-sm text-secondary-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($route->fiber_count)
                                <x-badge variant="info">{{ $route->fiber_count }} hilos</x-badge>
                            @else
                                <span class="text-sm text-secondary-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($route->status === 'active')
                                <x-badge variant="success" dot>Activo</x-badge>
                            @elseif($route->status === 'inactive')
                                <x-badge variant="danger" dot>Inactivo</x-badge>
                            @else
                                <x-badge variant="warning" dot>Mantenimiento</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('network.fiber-routes.show', $route) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('network.fiberroute.update')
                                    <a href="{{ route('network.fiber-routes.edit', $route) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('network.fiberroute.delete')
                                    <form action="{{ route('network.fiber-routes.destroy', $route) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar esta ruta de fibra?')">
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
                                <x-icon name="network" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay rutas de fibra</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando una nueva ruta de fibra entre nodos.</p>
                                @can('network.fiberroute.create')
                                    <div class="mt-4">
                                        <a href="{{ route('network.fiber-routes.create') }}">
                                            <x-button icon="plus">Nueva Ruta de Fibra</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if($routes->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $routes->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
