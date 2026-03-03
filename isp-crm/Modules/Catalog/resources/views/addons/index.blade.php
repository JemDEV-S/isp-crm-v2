@extends('layouts.app')

@section('title', 'Addons')

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Addons</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Botón Crear -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Addons</h1>
                <p class="mt-1 text-sm text-secondary-500">Servicios adicionales y extras para los planes</p>
            </div>
            @can('catalog.addon.create')
                <a href="{{ route('catalog.addons.create') }}">
                    <x-button icon="plus">
                        Nuevo Addon
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('catalog.addons.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre o código..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="is_active" label="Estado" placeholder="Todos">
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </x-select>

                    <x-select name="is_recurring" label="Tipo de Cobro" placeholder="Todos">
                        <option value="1" {{ ($filters['is_recurring'] ?? '') === '1' ? 'selected' : '' }}>Recurrente</option>
                        <option value="0" {{ ($filters['is_recurring'] ?? '') === '0' ? 'selected' : '' }}>Único</option>
                    </x-select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('catalog.addons.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Grid de Addons -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($addons as $addon)
                <x-card class="hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                                <x-icon name="tag" class="w-6 h-6 text-primary-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900">{{ $addon->name }}</h3>
                                <p class="text-xs text-secondary-500 font-mono">{{ $addon->code }}</p>
                            </div>
                        </div>
                        <x-badge :variant="$addon->is_active ? 'success' : 'danger'" size="sm" dot>
                            {{ $addon->is_active ? 'Activo' : 'Inactivo' }}
                        </x-badge>
                    </div>

                    @if($addon->description)
                        <p class="text-sm text-secondary-600 mb-4">{{ Str::limit($addon->description, 100) }}</p>
                    @endif

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-xs text-secondary-500">Precio</p>
                            <p class="text-2xl font-bold text-primary-600">S/ {{ number_format($addon->price, 2) }}</p>
                        </div>
                        <div>
                            <x-badge :variant="$addon->is_recurring ? 'info' : 'default'" size="sm">
                                {{ $addon->is_recurring ? 'Mensual' : 'Único' }}
                            </x-badge>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-secondary-200 flex justify-end gap-2">
                        <a href="{{ route('catalog.addons.show', $addon) }}">
                            <x-button variant="ghost" size="sm" icon="eye">Ver</x-button>
                        </a>
                        @can('catalog.addon.update')
                            <a href="{{ route('catalog.addons.edit', $addon) }}">
                                <x-button variant="secondary" size="sm" icon="pencil">Editar</x-button>
                            </a>
                        @endcan
                    </div>
                </x-card>
            @empty
                <div class="col-span-full">
                    <x-card class="text-center py-12">
                        <x-icon name="tag" class="w-12 h-12 text-secondary-300 mx-auto" />
                        <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay addons</h3>
                        <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo addon.</p>
                        @can('catalog.addon.create')
                            <div class="mt-4">
                                <a href="{{ route('catalog.addons.create') }}">
                                    <x-button icon="plus">Nuevo Addon</x-button>
                                </a>
                            </div>
                        @endcan
                    </x-card>
                </div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if($addons->hasPages())
            <div class="mt-6">
                {{ $addons->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
