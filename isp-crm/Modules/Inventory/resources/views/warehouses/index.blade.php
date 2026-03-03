@extends('layouts.app')

@section('title', 'Almacenes')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Almacenes</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Almacenes</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestión de almacenes y bodegas</p>
            </div>
            @can('inventory.warehouse.create')
                <a href="{{ route('inventory.warehouses.create') }}">
                    <x-button icon="plus">Nuevo Almacén</x-button>
                </a>
            @endcan
        </div>

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('inventory.warehouses.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-input name="search" label="Buscar" placeholder="Nombre, código..." :value="request('search')" icon="search" />
                    <x-select name="type" label="Tipo" placeholder="Todos">
                        @foreach($types as $type)
                            <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-select name="is_active" label="Estado" placeholder="Todos">
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </x-select>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('inventory.warehouses.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Grid de Almacenes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($warehouses as $warehouse)
                <x-card class="hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                                <x-icon name="package" class="w-6 h-6 text-primary-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900">{{ $warehouse->name }}</h3>
                                <p class="text-sm text-secondary-500">{{ $warehouse->code }}</p>
                            </div>
                        </div>
                        <x-badge :variant="$warehouse->is_active ? 'success' : 'danger'" size="sm" dot>
                            {{ $warehouse->is_active ? 'Activo' : 'Inactivo' }}
                        </x-badge>
                    </div>

                    <div class="space-y-2 text-sm text-secondary-600">
                        <div class="flex items-center gap-2">
                            <x-icon name="tag" class="w-4 h-4" />
                            <span>{{ $warehouse->type->label() }}</span>
                        </div>
                        @if($warehouse->address)
                            <div class="flex items-start gap-2">
                                <x-icon name="location" class="w-4 h-4 mt-0.5" />
                                <span class="line-clamp-2">{{ $warehouse->address }}</span>
                            </div>
                        @endif
                        @if($warehouse->user)
                            <div class="flex items-center gap-2">
                                <x-icon name="user" class="w-4 h-4" />
                                <span>{{ $warehouse->user->name }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-t border-secondary-200 flex justify-end gap-2">
                        <a href="{{ route('inventory.warehouses.show', $warehouse) }}">
                            <x-button variant="ghost" size="sm" icon="eye">Ver</x-button>
                        </a>
                        @can('inventory.warehouse.edit')
                            <a href="{{ route('inventory.warehouses.edit', $warehouse) }}">
                                <x-button variant="secondary" size="sm" icon="pencil">Editar</x-button>
                            </a>
                        @endcan
                    </div>
                </x-card>
            @empty
                <div class="col-span-full">
                    <x-card class="text-center py-12">
                        <x-icon name="package" class="w-12 h-12 text-secondary-300 mx-auto" />
                        <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay almacenes</h3>
                        <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo almacén.</p>
                        @can('inventory.warehouse.create')
                            <div class="mt-4">
                                <a href="{{ route('inventory.warehouses.create') }}">
                                    <x-button icon="plus">Nuevo Almacén</x-button>
                                </a>
                            </div>
                        @endcan
                    </x-card>
                </div>
            @endforelse
        </div>

        @if($warehouses->hasPages())
            <div class="mt-6">
                {{ $warehouses->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
