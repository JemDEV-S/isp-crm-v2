@extends('layouts.app')

@section('title', $promotion->name)

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('catalog.promotions.index') }}" class="text-secondary-500 hover:text-secondary-700">Promociones</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $promotion->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Acciones -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $promotion->name }}</h1>
                <div class="flex items-center gap-2 mt-2">
                    <x-badge :variant="$promotion->is_active ? 'success' : 'danger'" dot>
                        {{ $promotion->is_active ? 'Activa' : 'Inactiva' }}
                    </x-badge>
                    <x-badge variant="primary" size="sm">{{ $promotion->discount_type->label() }}</x-badge>
                    <x-badge variant="info" size="sm">{{ $promotion->applies_to->label() }}</x-badge>
                </div>
            </div>
            <div class="flex gap-2">
                @can('catalog.promotion.update')
                    <form action="{{ route('catalog.promotions.toggle-status', $promotion) }}" method="POST" class="inline">
                        @csrf
                        <x-button type="submit" variant="{{ $promotion->is_active ? 'warning' : 'success' }}" size="sm">
                            {{ $promotion->is_active ? 'Desactivar' : 'Activar' }}
                        </x-button>
                    </form>
                    <a href="{{ route('catalog.promotions.edit', $promotion) }}">
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
                            <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $promotion->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipo de Descuento</dt>
                            <dd class="mt-1">
                                <x-badge variant="primary" size="sm">{{ $promotion->discount_type->label() }}</x-badge>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Descripción</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $promotion->description ?? 'Sin descripción' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Detalles del Descuento -->
                <x-card title="Detalles del Descuento">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="bg-primary-50 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-secondary-500">Valor del Descuento</p>
                                    <p class="text-lg font-bold text-secondary-900">
                                        @if($promotion->discount_type->value === 'percentage')
                                            {{ $promotion->discount_value }}%
                                        @else
                                            S/ {{ number_format($promotion->discount_value, 2) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-info-50 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-info-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-secondary-500">Aplicable a</p>
                                    <p class="text-sm font-semibold text-secondary-900">{{ $promotion->applies_to->label() }}</p>
                                </div>
                            </div>
                        </div>

                        @if($promotion->min_months > 0)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Meses Mínimos</dt>
                                <dd class="mt-1 text-sm text-secondary-900">
                                    <x-badge variant="warning" size="sm">{{ $promotion->min_months }} meses</x-badge>
                                </dd>
                            </div>
                        @endif
                    </div>
                </x-card>

                <!-- Vigencia -->
                @if($promotion->valid_from || $promotion->valid_until)
                    <x-card title="Vigencia">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            @if($promotion->valid_from)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">Válida Desde</dt>
                                    <dd class="mt-1 text-sm text-secondary-900">
                                        {{ $promotion->valid_from->format('d/m/Y') }}
                                    </dd>
                                </div>
                            @endif
                            @if($promotion->valid_until)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">Válida Hasta</dt>
                                    <dd class="mt-1 text-sm text-secondary-900">
                                        {{ $promotion->valid_until->format('d/m/Y') }}
                                        @if($promotion->valid_until->isPast())
                                            <x-badge variant="danger" size="sm" class="ml-2">Expirada</x-badge>
                                        @elseif($promotion->valid_until->isFuture() && $promotion->valid_until->diffInDays() <= 7)
                                            <x-badge variant="warning" size="sm" class="ml-2">Próxima a expirar</x-badge>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </x-card>
                @endif

                <!-- Planes Asociados -->
                @if($promotion->plans->count() > 0)
                    <x-card title="Planes Asociados">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($promotion->plans as $plan)
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
                @endif
            </div>

            <!-- Sidebar (1/3) -->
            <div class="space-y-6">
                <!-- Estadísticas de Uso -->
                <x-card title="Estadísticas">
                    <div class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Usos Actuales</dt>
                            <dd class="mt-1 text-2xl font-bold text-secondary-900">{{ $promotion->current_uses }}</dd>
                        </div>
                        @if($promotion->max_uses)
                            <div class="pt-4 border-t border-secondary-200">
                                <dt class="text-sm font-medium text-secondary-500">Usos Máximos</dt>
                                <dd class="mt-1 text-lg font-semibold text-secondary-900">{{ $promotion->max_uses }}</dd>
                                <div class="mt-2 w-full bg-secondary-200 rounded-full h-2">
                                    <div class="bg-primary-600 h-2 rounded-full" style="width: {{ ($promotion->current_uses / $promotion->max_uses) * 100 }}%"></div>
                                </div>
                                <p class="mt-1 text-xs text-secondary-500">
                                    {{ number_format(($promotion->current_uses / $promotion->max_uses) * 100, 1) }}% utilizado
                                </p>
                            </div>
                        @else
                            <div class="pt-4 border-t border-secondary-200">
                                <p class="text-sm text-secondary-500">Sin límite de usos</p>
                            </div>
                        @endif
                    </div>
                </x-card>

                <!-- Información del Sistema -->
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">UUID</dt>
                            <dd class="mt-1 text-xs font-mono text-secondary-900 break-all">{{ $promotion->uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $promotion->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $promotion->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Acciones -->
                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('catalog.promotion.update')
                            <a href="{{ route('catalog.promotions.edit', $promotion) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar Promoción</x-button>
                            </a>
                        @endcan
                        @can('catalog.promotion.delete')
                            <form action="{{ route('catalog.promotions.destroy', $promotion) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar esta promoción? Esta acción no se puede deshacer.')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                    Eliminar Promoción
                                </x-button>
                            </form>
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
