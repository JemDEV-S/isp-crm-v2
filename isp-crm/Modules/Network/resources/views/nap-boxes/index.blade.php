@extends('layouts.app')

@section('title', 'Cajas NAP')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Cajas NAP</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Cajas NAP</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona las cajas de distribución de fibra óptica</p>
            </div>
            @can('network.napbox.create')
                <a href="{{ route('network.nap-boxes.create') }}">
                    <x-button icon="plus">
                        Nueva Caja NAP
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('network.nap-boxes.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Código, nombre o dirección..."
                        :value="request('search')"
                        icon="search"
                    />

                    <x-select name="node_id" label="Nodo" placeholder="Todos los nodos">
                        @foreach($nodes as $node)
                            <option value="{{ $node->id }}" {{ request('node_id') == $node->id ? 'selected' : '' }}>
                                {{ $node->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="status" label="Estado" placeholder="Todos los estados">
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Mantenimiento</option>
                    </x-select>

                    <div class="flex items-end">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="has_free_ports" value="1" {{ request('has_free_ports') ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Solo con puertos libres</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('network.nap-boxes.index') }}">
                        <x-button type="button" variant="ghost">
                            Limpiar
                        </x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">
                        Filtrar
                    </x-button>
                </div>
            </form>
        </x-card>

        <!-- Tabla -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Código
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Nodo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Tipo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Puertos
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($napBoxes as $napBox)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-secondary-900">{{ $napBox->code }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-secondary-900">{{ $napBox->name }}</div>
                            <div class="text-xs text-secondary-500">{{ Str::limit($napBox->address, 30) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">{{ $napBox->node->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">{{ $napBox->type }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="text-sm text-secondary-900">
                                    <span class="font-medium text-success-600">{{ $napBox->free_ports_count }}</span>
                                    / {{ $napBox->total_ports }}
                                </div>
                                <div class="w-20 bg-secondary-200 rounded-full h-2">
                                    <div class="bg-success-500 h-2 rounded-full"
                                         style="width: {{ $napBox->total_ports > 0 ? ($napBox->free_ports_count / $napBox->total_ports * 100) : 0 }}%">
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($napBox->status === 'active')
                                <x-badge variant="success" dot>Activo</x-badge>
                            @elseif($napBox->status === 'inactive')
                                <x-badge variant="danger" dot>Inactivo</x-badge>
                            @else
                                <x-badge variant="warning" dot>Mantenimiento</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('network.nap-boxes.show', $napBox) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('network.napbox.update')
                                    <a href="{{ route('network.nap-boxes.edit', $napBox) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('network.napbox.delete')
                                    <form action="{{ route('network.nap-boxes.destroy', $napBox) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar esta caja NAP?')">
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
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay cajas NAP</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando una nueva caja de distribución.</p>
                                @can('network.napbox.create')
                                    <div class="mt-4">
                                        <a href="{{ route('network.nap-boxes.create') }}">
                                            <x-button icon="plus">Nueva Caja NAP</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if($napBoxes->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $napBoxes->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
