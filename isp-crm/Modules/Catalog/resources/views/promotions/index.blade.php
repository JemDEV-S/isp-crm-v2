@extends('layouts.app')

@section('title', 'Promociones')

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Promociones</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Botón Crear -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Promociones</h1>
                <p class="mt-1 text-sm text-secondary-500">Administra las promociones y descuentos disponibles</p>
            </div>
            @can('catalog.promotion.create')
                <a href="{{ route('catalog.promotions.create') }}">
                    <x-button icon="plus">
                        Nueva Promoción
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('catalog.promotions.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre o código..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="is_active" label="Estado" placeholder="Todos">
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Activas</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactivas</option>
                    </x-select>

                    <x-select name="discount_type" label="Tipo de Descuento" placeholder="Todos">
                        <option value="percentage" {{ ($filters['discount_type'] ?? '') === 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                        <option value="fixed" {{ ($filters['discount_type'] ?? '') === 'fixed' ? 'selected' : '' }}>Monto Fijo</option>
                    </x-select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('catalog.promotions.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Grid de Promociones -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($promotions as $promotion)
                <x-card class="hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-secondary-900">{{ $promotion->name }}</h3>
                            <p class="text-sm text-secondary-500 font-mono">{{ $promotion->code }}</p>
                        </div>
                        <x-badge :variant="$promotion->is_active ? 'success' : 'danger'" size="sm" dot>
                            {{ $promotion->is_active ? 'Activa' : 'Inactiva' }}
                        </x-badge>
                    </div>

                    @if($promotion->description)
                        <p class="text-sm text-secondary-600 mb-4">{{ Str::limit($promotion->description, 100) }}</p>
                    @endif

                    <div class="space-y-2 mb-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-secondary-500">Descuento:</span>
                            <span class="font-semibold text-primary-600">
                                @if($promotion->discount_type->value === 'percentage')
                                    {{ $promotion->discount_value }}%
                                @else
                                    S/ {{ number_format($promotion->discount_value, 2) }}
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-secondary-500">Aplicable a:</span>
                            <x-badge variant="info" size="sm">{{ $promotion->applies_to->label() }}</x-badge>
                        </div>
                        @if($promotion->valid_from && $promotion->valid_until)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-secondary-500">Vigencia:</span>
                                <span class="text-xs text-secondary-600">
                                    {{ $promotion->valid_from->format('d/m/Y') }} - {{ $promotion->valid_until->format('d/m/Y') }}
                                </span>
                            </div>
                        @endif
                        @if($promotion->max_uses)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-secondary-500">Usos:</span>
                                <span class="text-xs text-secondary-600">
                                    {{ $promotion->current_uses }} / {{ $promotion->max_uses }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="pt-4 border-t border-secondary-200 flex justify-end gap-2">
                        <a href="{{ route('catalog.promotions.show', $promotion) }}">
                            <x-button variant="ghost" size="sm" icon="eye">Ver</x-button>
                        </a>
                        @can('catalog.promotion.update')
                            <a href="{{ route('catalog.promotions.edit', $promotion) }}">
                                <x-button variant="secondary" size="sm" icon="pencil">Editar</x-button>
                            </a>
                        @endcan
                    </div>
                </x-card>
            @empty
                <div class="col-span-full">
                    <x-card class="text-center py-12">
                        <x-icon name="tag" class="w-12 h-12 text-secondary-300 mx-auto" />
                        <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay promociones</h3>
                        <p class="mt-1 text-sm text-secondary-500">Comienza creando una nueva promoción.</p>
                        @can('catalog.promotion.create')
                            <div class="mt-4">
                                <a href="{{ route('catalog.promotions.create') }}">
                                    <x-button icon="plus">Nueva Promoción</x-button>
                                </a>
                            </div>
                        @endcan
                    </x-card>
                </div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if($promotions->hasPages())
            <div class="mt-6">
                {{ $promotions->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
