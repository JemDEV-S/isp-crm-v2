@extends('layouts.app')

@section('title', 'Planes de Servicio')

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Planes</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Botón Crear -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Planes de Servicio</h1>
                <p class="mt-1 text-sm text-secondary-500">Administra los planes disponibles para tus clientes</p>
            </div>
            @can('catalog.plan.create')
                <a href="{{ route('catalog.plans.create') }}">
                    <x-button icon="plus">
                        Nuevo Plan
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('catalog.plans.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre o código..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="technology" label="Tecnología" placeholder="Todas">
                        @foreach($technologies as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['technology'] ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="is_active" label="Estado" placeholder="Todos">
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </x-select>

                    <x-select name="is_visible" label="Visibilidad" placeholder="Todos">
                        <option value="1" {{ ($filters['is_visible'] ?? '') === '1' ? 'selected' : '' }}>Visibles</option>
                        <option value="0" {{ ($filters['is_visible'] ?? '') === '0' ? 'selected' : '' }}>Ocultos</option>
                    </x-select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('catalog.plans.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Tabla de Datos -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Plan
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Tecnología
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Velocidad
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Precio
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($plans as $plan)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-secondary-900">{{ $plan->name }}</div>
                                    <div class="text-sm text-secondary-500">{{ $plan->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge variant="primary" size="sm">
                                {{ $plan->technology->label() }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-900">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                {{ $plan->download_speed }} Mbps
                            </div>
                            <div class="flex items-center gap-1 text-secondary-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                </svg>
                                {{ $plan->upload_speed }} Mbps
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-semibold text-secondary-900">S/ {{ number_format($plan->price, 2) }}</div>
                            @if($plan->installation_fee > 0)
                                <div class="text-xs text-secondary-500">Instalación: S/ {{ number_format($plan->installation_fee, 2) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex flex-col items-center gap-1">
                                <x-badge :variant="$plan->is_active ? 'success' : 'danger'" dot size="sm">
                                    {{ $plan->is_active ? 'Activo' : 'Inactivo' }}
                                </x-badge>
                                @if(!$plan->is_visible)
                                    <x-badge variant="warning" size="sm">Oculto</x-badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('catalog.plans.show', $plan) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('catalog.plan.update')
                                    <a href="{{ route('catalog.plans.edit', $plan) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('catalog.plan.delete')
                                    <form action="{{ route('catalog.plans.destroy', $plan) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este plan?')">
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
                                <x-icon name="tag" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay planes</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo plan de servicio.</p>
                                @can('catalog.plan.create')
                                    <div class="mt-4">
                                        <a href="{{ route('catalog.plans.create') }}">
                                            <x-button icon="plus">Nuevo Plan</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            <!-- Paginación -->
            @if($plans->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $plans->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
