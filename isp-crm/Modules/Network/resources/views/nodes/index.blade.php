@extends('layouts.app')

@section('title', 'Nodos de Red')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Nodos de Red</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Nodos de Red</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona los nodos de red del sistema</p>
            </div>
            @can('network.device.create')
                <a href="{{ route('network.nodes.create') }}">
                    <x-button icon="plus">
                        Nuevo Nodo
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- filters -->
        <x-card>
            <form method="GET" action="{{ route('network.nodes.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre, IP o ubicación..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="type" label="Tipo" placeholder="Todos los tipos">
                        @foreach (\Modules\Network\Enums\NodeType::cases() as $type)
                            <option value="{{ $type->value }}" {{ ($filters['type'] ?? '') == $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-select name="status" label="Estado" placeholder="Todos los estados">
                        @foreach (\Modules\Network\Enums\NodeStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('network.nodes.index') }}">
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
        <!-- Nodes Table -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Codigo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Tipo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Dirección
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse ($nodes as $node)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-900">
                            {{ $node->code }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-900">
                            {{ $node->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge variant="default" size="sm">{{ $node->type->label() }}</x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-900">
                            {{ $node->address }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$node->status->value === 'active' ? 'success' : ($node->status->value === 'maintenance' ? 'warning' : 'danger')" dot>
                                {{ $node->status->label() }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex-justify-end-items-center gap-2">
                                <a href="{{ route('network.nodes.show', $node) }}" class="text-primary-600 hover:text-primary-900">
                                    Ver
                                </a>
                                @can('network.device.edit')
                                    <a href="{{ route('network.nodes.edit', $node) }}" class="text-secondary-600 hover:text-secondary-900">
                                        Editar
                                    </a>
                                @endcan
                                @can('network.device.delete')
                                    <form action="{{ route('network.nodes.destroy', $node) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Estás seguro de que deseas eliminar este nodo?')">
                                            Eliminar
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
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay nodos de red aún
                                </h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo Node de red.</p>
                                @can('network.device.create')
                                    <div class="mt-4">
                                        <a href="{{ route('network.nodes.create') }}">
                                            <x-button icon="plus">Nuevo Nodo</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>
            @if($nodes->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $nodes->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
