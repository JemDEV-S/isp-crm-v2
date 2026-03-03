@extends('layouts.app')

@section('title', $plan->name)

@section('breadcrumb')
    <span class="text-secondary-500">Catálogo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('catalog.plans.index') }}" class="text-secondary-500 hover:text-secondary-700">Planes</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $plan->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Acciones -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $plan->name }}</h1>
                <div class="flex items-center gap-2 mt-2">
                    <x-badge :variant="$plan->is_active ? 'success' : 'danger'" dot>
                        {{ $plan->is_active ? 'Activo' : 'Inactivo' }}
                    </x-badge>
                    @if($plan->is_visible)
                        <x-badge variant="success" size="sm">Visible</x-badge>
                    @else
                        <x-badge variant="warning" size="sm">Oculto</x-badge>
                    @endif
                    <x-badge variant="primary" size="sm">{{ $plan->technology->label() }}</x-badge>
                </div>
            </div>
            <div class="flex gap-2">
                @can('catalog.plan.update')
                    <form action="{{ route('catalog.plans.toggle-status', $plan) }}" method="POST" class="inline">
                        @csrf
                        <x-button type="submit" variant="{{ $plan->is_active ? 'warning' : 'success' }}" size="sm">
                            {{ $plan->is_active ? 'Desactivar' : 'Activar' }}
                        </x-button>
                    </form>
                    <a href="{{ route('catalog.plans.edit', $plan) }}">
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
                            <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $plan->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tecnología</dt>
                            <dd class="mt-1">
                                <x-badge variant="primary" size="sm">{{ $plan->technology->label() }}</x-badge>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Descripción</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $plan->description ?? 'Sin descripción' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Características Técnicas -->
                <x-card title="Características Técnicas">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="bg-primary-50 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-secondary-500">Velocidad de Bajada</p>
                                    <p class="text-lg font-bold text-secondary-900">{{ $plan->download_speed }} Mbps</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-success-50 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-success-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-secondary-500">Velocidad de Subida</p>
                                    <p class="text-lg font-bold text-secondary-900">{{ $plan->upload_speed }} Mbps</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Prioridad</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                <x-badge variant="info" size="sm">Nivel {{ $plan->priority }}</x-badge>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Burst</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                @if($plan->burst_enabled)
                                    <x-badge variant="success" size="sm">Habilitado</x-badge>
                                @else
                                    <x-badge variant="default" size="sm">Deshabilitado</x-badge>
                                @endif
                            </dd>
                        </div>
                    </div>
                </x-card>

                <!-- Configuración de Red -->
                @if($plan->router_profile || $plan->olt_profile)
                    <x-card title="Configuración de Red">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            @if($plan->router_profile)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">Perfil de RouterOS</dt>
                                    <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $plan->router_profile }}</dd>
                                </div>
                            @endif
                            @if($plan->olt_profile)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">Perfil de OLT</dt>
                                    <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $plan->olt_profile }}</dd>
                                </div>
                            @endif
                        </dl>
                    </x-card>
                @endif

                <!-- Promociones Activas -->
                @if($plan->promotions->count() > 0)
                    <x-card title="Promociones Asociadas">
                        <div class="space-y-3">
                            @foreach($plan->promotions as $promotion)
                                <div class="flex items-start justify-between p-3 bg-secondary-50 rounded-lg">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-medium text-secondary-900">{{ $promotion->name }}</h4>
                                            <x-badge :variant="$promotion->is_active ? 'success' : 'danger'" size="sm">
                                                {{ $promotion->is_active ? 'Activa' : 'Inactiva' }}
                                            </x-badge>
                                        </div>
                                        <p class="mt-1 text-xs text-secondary-500">
                                            {{ $promotion->discount_type->label() }}:
                                            @if($promotion->discount_type->value === 'percentage')
                                                {{ $promotion->discount_value }}% de descuento
                                            @else
                                                S/ {{ number_format($promotion->discount_value, 2) }} de descuento
                                            @endif
                                        </p>
                                        @if($promotion->valid_from && $promotion->valid_until)
                                            <p class="mt-1 text-xs text-secondary-500">
                                                Válido: {{ $promotion->valid_from->format('d/m/Y') }} - {{ $promotion->valid_until->format('d/m/Y') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                <!-- Addons Compatibles -->
                @if($plan->addons->count() > 0)
                    <x-card title="Addons Compatibles">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($plan->addons as $addon)
                                <div class="flex items-start gap-3 p-3 bg-secondary-50 rounded-lg">
                                    <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <x-icon name="tag" class="w-4 h-4 text-primary-600" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-secondary-900">{{ $addon->name }}</h4>
                                        <p class="mt-1 text-xs text-secondary-500">
                                            S/ {{ number_format($addon->price, 2) }}
                                            {{ $addon->is_recurring ? '/ mes' : '(único)' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif
            </div>

            <!-- Sidebar (1/3) -->
            <div class="space-y-6">
                <!-- Precios -->
                <x-card title="Precios">
                    <div class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Precio Mensual</dt>
                            <dd class="mt-1 text-2xl font-bold text-secondary-900">
                                S/ {{ number_format($plan->price, 2) }}
                            </dd>
                        </div>
                        @if($plan->installation_fee > 0)
                            <div class="pt-4 border-t border-secondary-200">
                                <dt class="text-sm font-medium text-secondary-500">Costo de Instalación</dt>
                                <dd class="mt-1 text-lg font-semibold text-secondary-900">
                                    S/ {{ number_format($plan->installation_fee, 2) }}
                                </dd>
                            </div>
                        @endif
                    </div>
                </x-card>

                <!-- Información del Sistema -->
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">UUID</dt>
                            <dd class="mt-1 text-xs font-mono text-secondary-900 break-all">{{ $plan->uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $plan->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $plan->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        @if($plan->created_by)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Creado por</dt>
                                <dd class="mt-1 text-sm text-secondary-900">
                                    {{ $plan->creator->name ?? 'N/A' }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                <!-- Acciones -->
                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('catalog.plan.update')
                            <a href="{{ route('catalog.plans.edit', $plan) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar Plan</x-button>
                            </a>
                            <form action="{{ route('catalog.plans.toggle-visibility', $plan) }}" method="POST">
                                @csrf
                                <x-button type="submit" variant="outline" class="w-full">
                                    {{ $plan->is_visible ? 'Ocultar del Catálogo' : 'Mostrar en Catálogo' }}
                                </x-button>
                            </form>
                        @endcan
                        @can('catalog.plan.delete')
                            <form action="{{ route('catalog.plans.destroy', $plan) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar este plan? Esta acción no se puede deshacer.')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                    Eliminar Plan
                                </x-button>
                            </form>
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
