@extends('layouts.app')

@section('title', $addon->name)

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('catalog.addons.index') }}" class="text-secondary-500 hover:text-secondary-700">Addons</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $addon->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Acciones -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $addon->name }}</h1>
                <div class="flex items-center gap-2 mt-2">
                    <x-badge :variant="$addon->is_active ? 'success' : 'danger'" dot>
                        {{ $addon->is_active ? 'Activo' : 'Inactivo' }}
                    </x-badge>
                    <x-badge :variant="$addon->is_recurring ? 'info' : 'default'" size="sm">
                        {{ $addon->is_recurring ? 'Recurrente' : 'Único' }}
                    </x-badge>
                </div>
            </div>
            <div class="flex gap-2">
                @can('catalog.addon.update')
                    <form action="{{ route('catalog.addons.toggle-status', $addon) }}" method="POST" class="inline">
                        @csrf
                        <x-button type="submit" variant="{{ $addon->is_active ? 'warning' : 'success' }}" size="sm">
                            {{ $addon->is_active ? 'Desactivar' : 'Activar' }}
                        </x-button>
                    </form>
                    <a href="{{ route('catalog.addons.edit', $addon) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Layout de 2 columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal (2/3) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Información General -->
                <x-card title="Información General">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Código</dt>
                            <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $addon->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipo de Cobro</dt>
                            <dd class="mt-1">
                                <x-badge :variant="$addon->is_recurring ? 'info' : 'default'" size="sm">
                                    {{ $addon->is_recurring ? 'Recurrente (Mensual)' : 'Pago Único' }}
                                </x-badge>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Descripción</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $addon->description ?? 'Sin descripción' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Detalles del Precio -->
                <x-card title="Precio">
                    <div class="bg-primary-50 rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-secondary-500">Precio del Addon</p>
                                <p class="text-3xl font-bold text-primary-600">S/ {{ number_format($addon->price, 2) }}</p>
                                @if($addon->is_recurring)
                                    <p class="mt-1 text-xs text-secondary-600">Por mes</p>
                                @else
                                    <p class="mt-1 text-xs text-secondary-600">Pago único</p>
                                @endif
                            </div>
                            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </x-card>

                <!-- Planes que incluyen este Addon -->
                @if($addon->plans->count() > 0)
                    <x-card title="Planes que incluyen este Addon">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($addon->plans as $plan)
                                <div class="flex items-start gap-3 p-3 bg-secondary-50 rounded-lg">
                                    <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <x-icon name="tag" class="w-4 h-4 text-primary-600" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-secondary-900">{{ $plan->name }}</h4>
                                        <p class="mt-1 text-xs text-secondary-500">
                                            {{ $plan->download_speed }} Mbps - S/ {{ number_format($plan->price, 2) }}
                                        </p>
                                    </div>
                                    <a href="{{ route('catalog.plans.show', $plan) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="eye" class="w-4 h-4" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @else
                    <x-card>
                        <div class="text-center py-6">
                            <x-icon name="tag" class="w-8 h-8 text-secondary-300 mx-auto" />
                            <p class="mt-2 text-sm text-secondary-500">Este addon no está asociado a ningún plan</p>
                        </div>
                    </x-card>
                @endif
            </div>

            <!-- Sidebar (1/3) -->
            <div class="space-y-6">
                <!-- Información del Sistema -->
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">UUID</dt>
                            <dd class="mt-1 text-xs font-mono text-secondary-900 break-all">{{ $addon->uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $addon->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $addon->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        @if($addon->created_by)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Creado por</dt>
                                <dd class="mt-1 text-sm text-secondary-900">
                                    {{ $addon->creator->name ?? 'N/A' }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                <!-- Acciones -->
                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('catalog.addon.update')
                            <a href="{{ route('catalog.addons.edit', $addon) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar Addon</x-button>
                            </a>
                        @endcan
                        @can('catalog.addon.delete')
                            <form action="{{ route('catalog.addons.destroy', $addon) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar este addon? Esta acción no se puede deshacer.')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                    Eliminar Addon
                                </x-button>
                            </form>
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
