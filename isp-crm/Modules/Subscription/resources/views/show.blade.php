@extends('layouts.app')

@section('title', 'Detalle de Suscripción')

@section('breadcrumb')
    <span class="text-secondary-500">Servicios</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('subscriptions.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Suscripciones
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">{{ $subscription->subscription_code }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">
                    Suscripción {{ $subscription->subscription_code }}
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    @if($subscription->status === 'pending')
                        <x-badge variant="warning" dot>Pendiente</x-badge>
                    @elseif($subscription->status === 'active')
                        <x-badge variant="success" dot>Activa</x-badge>
                    @elseif($subscription->status === 'suspended')
                        <x-badge variant="danger" dot>Suspendida</x-badge>
                    @else
                        <x-badge variant="secondary" dot>Cancelada</x-badge>
                    @endif
                    @if($subscription->workflow_state)
                        <x-badge variant="info">{{ $subscription->workflow_state }}</x-badge>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                @can('subscription.update')
                    <a href="{{ route('subscriptions.edit', $subscription) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Precio Mensual</p>
                        <p class="mt-1 text-2xl font-bold text-primary-600">${{ number_format($subscription->monthly_price, 2) }}</p>
                    </div>
                    <div class="p-3 bg-primary-100 rounded-full">
                        <x-icon name="currency-dollar" class="w-6 h-6 text-primary-600" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Día de Facturación</p>
                        <p class="mt-1 text-2xl font-bold text-secondary-900">{{ $subscription->billing_day }}</p>
                        <p class="text-xs text-secondary-500">
                            @if($subscription->billing_cycle === 'monthly')
                                Mensual
                            @elseif($subscription->billing_cycle === 'quarterly')
                                Trimestral
                            @elseif($subscription->billing_cycle === 'semiannual')
                                Semestral
                            @else
                                Anual
                            @endif
                        </p>
                    </div>
                    <div class="p-3 bg-info-100 rounded-full">
                        <x-icon name="calendar" class="w-6 h-6 text-info-600" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Fecha de Inicio</p>
                        <p class="mt-1 text-lg font-bold text-secondary-900">
                            {{ $subscription->start_date?->format('d/m/Y') ?? '-' }}
                        </p>
                    </div>
                    <div class="p-3 bg-success-100 rounded-full">
                        <x-icon name="play" class="w-6 h-6 text-success-600" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Fecha de Fin</p>
                        <p class="mt-1 text-lg font-bold text-secondary-900">
                            {{ $subscription->end_date?->format('d/m/Y') ?? 'Activa' }}
                        </p>
                    </div>
                    <div class="p-3 bg-warning-100 rounded-full">
                        <x-icon name="stop" class="w-6 h-6 text-warning-600" />
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Layout 2 columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Cliente -->
                <x-card title="Información del Cliente">
                    <div class="flex items-center gap-4 p-4 bg-secondary-50 rounded-lg">
                        <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center">
                            <x-icon name="user" class="w-8 h-8 text-primary-600" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-secondary-900">{{ $subscription->customer->name }}</h3>
                            <p class="text-sm text-secondary-600">{{ $subscription->customer->document_number }}</p>
                            @if($subscription->customer->email)
                                <p class="text-sm text-secondary-600">{{ $subscription->customer->email }}</p>
                            @endif
                            @if($subscription->customer->phone)
                                <p class="text-sm text-secondary-600">{{ $subscription->customer->phone }}</p>
                            @endif
                        </div>
                        <a href="{{ route('customers.show', $subscription->customer) }}" class="text-primary-600 hover:text-primary-900">
                            <x-icon name="arrow-right" class="w-5 h-5" />
                        </a>
                    </div>
                </x-card>

                <!-- Plan de Servicio -->
                <x-card title="Plan de Servicio">
                    <div class="flex items-center gap-4 p-4 bg-primary-50 rounded-lg">
                        <div class="w-16 h-16 rounded-full bg-primary-200 flex items-center justify-center">
                            <x-icon name="server" class="w-8 h-8 text-primary-700" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-secondary-900">{{ $subscription->plan->name }}</h3>
                            <p class="text-sm text-secondary-600">
                                Velocidad: {{ $subscription->plan->download_speed }} / {{ $subscription->plan->upload_speed }} Mbps
                            </p>
                            <p class="text-sm font-semibold text-primary-600">
                                ${{ number_format($subscription->plan->monthly_price, 2) }}/mes
                            </p>
                            @if($subscription->plan->description)
                                <p class="text-sm text-secondary-500 mt-1">{{ $subscription->plan->description }}</p>
                            @endif
                        </div>
                    </div>
                </x-card>

                <!-- Dirección de Servicio -->
                <x-card title="Dirección de Instalación">
                    <div class="p-4 bg-secondary-50 rounded-lg">
                        <div class="flex items-start gap-3">
                            <x-icon name="location-marker" class="w-5 h-5 text-secondary-600 flex-shrink-0 mt-0.5" />
                            <div>
                                <p class="text-sm font-medium text-secondary-900">
                                    {{ $subscription->serviceAddress->full_address ?? 'No especificada' }}
                                </p>
                                @if($subscription->serviceAddress)
                                    @if($subscription->serviceAddress->reference)
                                        <p class="text-sm text-secondary-600 mt-1">
                                            Ref: {{ $subscription->serviceAddress->reference }}
                                        </p>
                                    @endif
                                    @if($subscription->serviceAddress->latitude && $subscription->serviceAddress->longitude)
                                        <p class="text-xs text-secondary-500 mt-1 font-mono">
                                            {{ $subscription->serviceAddress->latitude }}, {{ $subscription->serviceAddress->longitude }}
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </x-card>

                <!-- Instancia de Servicio -->
                @if($subscription->serviceInstance)
                    <x-card title="Detalles Técnicos del Servicio">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            @if($subscription->serviceInstance->assigned_ip)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">IP Asignada</dt>
                                    <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $subscription->serviceInstance->assigned_ip }}</dd>
                                </div>
                            @endif
                            @if($subscription->serviceInstance->pppoe_username)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">Usuario PPPoE</dt>
                                    <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $subscription->serviceInstance->pppoe_username }}</dd>
                                </div>
                            @endif
                            @if($subscription->serviceInstance->nap_port_id)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">Puerto NAP</dt>
                                    <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->serviceInstance->napPort->port_number ?? 'N/A' }}</dd>
                                </div>
                            @endif
                            @if($subscription->serviceInstance->onu_serial)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">ONU Serial</dt>
                                    <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $subscription->serviceInstance->onu_serial }}</dd>
                                </div>
                            @endif
                            @if($subscription->serviceInstance->router_mac)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">MAC Router</dt>
                                    <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $subscription->serviceInstance->router_mac }}</dd>
                                </div>
                            @endif
                            @if($subscription->serviceInstance->installation_date)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">Fecha de Instalación</dt>
                                    <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->serviceInstance->installation_date->format('d/m/Y') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </x-card>
                @endif

                <!-- Servicios Adicionales -->
                @if($subscription->addons->count() > 0)
                    <x-card title="Servicios Adicionales">
                        <div class="space-y-2">
                            @foreach($subscription->addons as $addon)
                                <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-secondary-900">{{ $addon->name }}</p>
                                        @if($addon->description)
                                            <p class="text-xs text-secondary-500">{{ $addon->description }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-secondary-900">
                                            +${{ number_format($addon->price, 2) }}
                                        </p>
                                        <p class="text-xs text-secondary-500">
                                            {{ $addon->billing_type === 'recurring' ? '/mes' : 'único' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                <!-- Historial de Estados -->
                @if($subscription->statusHistory->count() > 0)
                    <x-card title="Historial de Estados">
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($subscription->statusHistory as $history)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-secondary-200" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                                        @if($history->status === 'active') bg-success-500
                                                        @elseif($history->status === 'suspended') bg-danger-500
                                                        @elseif($history->status === 'cancelled') bg-secondary-500
                                                        @else bg-warning-500
                                                        @endif">
                                                        <x-icon name="check" class="w-5 h-5 text-white" />
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                    <div>
                                                        <p class="text-sm text-secondary-900">
                                                            Estado cambiado a
                                                            <span class="font-medium">
                                                                @if($history->status === 'pending') Pendiente
                                                                @elseif($history->status === 'active') Activa
                                                                @elseif($history->status === 'suspended') Suspendida
                                                                @else Cancelada
                                                                @endif
                                                            </span>
                                                        </p>
                                                        @if($history->reason)
                                                            <p class="text-sm text-secondary-500">{{ $history->reason }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-sm text-secondary-500">
                                                        <time datetime="{{ $history->created_at }}">{{ $history->created_at->format('d/m/Y H:i') }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </x-card>
                @endif

                <!-- Notas -->
                @if($subscription->notes()->count() > 0)
                    <x-card title="Notas">
                        <div class="space-y-3">
                            @foreach($subscription->notes as $note)
                                <div class="p-4 border border-secondary-200 rounded-lg">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm text-secondary-900">{{ $note->content }}</p>
                                            @if($note->is_internal)
                                                <x-badge variant="warning" class="mt-2">Nota Interna</x-badge>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-2 flex items-center gap-2 text-xs text-secondary-500">
                                        <span>{{ $note->created_by_name ?? 'Sistema' }}</span>
                                        <span>•</span>
                                        <time datetime="{{ $note->created_at }}">{{ $note->created_at->format('d/m/Y H:i') }}</time>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Información del Sistema -->
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Código</dt>
                            <dd class="mt-1 text-sm font-mono text-secondary-900">{{ $subscription->subscription_code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Creada</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $subscription->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $subscription->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        @if($subscription->discount_percentage)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Descuento</dt>
                                <dd class="mt-1 text-sm text-success-600 font-semibold">
                                    {{ $subscription->discount_percentage }}%
                                </dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                <!-- Acciones -->
                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('subscription.update')
                            <a href="{{ route('subscriptions.edit', $subscription) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar</x-button>
                            </a>
                        @endcan

                        @if($subscription->status === 'pending')
                            @can('subscription.activate')
                                <form action="{{ route('subscriptions.activate', $subscription) }}" method="POST">
                                    @csrf
                                    <x-button type="submit" variant="success" class="w-full" icon="check">
                                        Activar Servicio
                                    </x-button>
                                </form>
                            @endcan
                        @endif

                        @if($subscription->status === 'active')
                            @can('subscription.suspend')
                                <form action="{{ route('subscriptions.suspend', $subscription) }}" method="POST"
                                      onsubmit="return confirm('¿Está seguro de suspender esta suscripción?')">
                                    @csrf
                                    <x-button type="submit" variant="warning" class="w-full" icon="pause">
                                        Suspender
                                    </x-button>
                                </form>
                            @endcan

                            @can('subscription.change-plan')
                                <a href="{{ route('subscriptions.change-plan', $subscription) }}" class="block">
                                    <x-button variant="info" class="w-full" icon="switch-horizontal">
                                        Cambiar Plan
                                    </x-button>
                                </a>
                            @endcan
                        @endif

                        @if($subscription->status === 'suspended')
                            @can('subscription.reactivate')
                                <form action="{{ route('subscriptions.reactivate', $subscription) }}" method="POST">
                                    @csrf
                                    <x-button type="submit" variant="success" class="w-full" icon="refresh">
                                        Reactivar
                                    </x-button>
                                </form>
                            @endcan
                        @endif

                        @if(in_array($subscription->status, ['pending', 'active', 'suspended']))
                            @can('subscription.cancel')
                                <form action="{{ route('subscriptions.cancel', $subscription) }}" method="POST"
                                      onsubmit="return confirm('¿Está seguro de cancelar esta suscripción? Esta acción no se puede revertir.')">
                                    @csrf
                                    <x-button type="submit" variant="danger" class="w-full" icon="x">
                                        Cancelar Suscripción
                                    </x-button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </x-card>

                <!-- Promoción Activa -->
                @if($subscription->promotion)
                    <x-card title="Promoción Activa">
                        <div class="p-3 bg-success-50 rounded-lg">
                            <p class="text-sm font-medium text-success-900">{{ $subscription->promotion->name }}</p>
                            <p class="text-xs text-success-700 mt-1">
                                @if($subscription->promotion->discount_type === 'percentage')
                                    {{ $subscription->promotion->discount_value }}% de descuento
                                @else
                                    ${{ number_format($subscription->promotion->discount_value, 2) }} de descuento
                                @endif
                            </p>
                        </div>
                    </x-card>
                @endif
            </div>
        </div>
    </div>
@endsection
