@extends('layouts.app')

@section('title', 'Suscripciones')

@section('breadcrumb')
    <span class="text-secondary-500">Servicios</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Suscripciones</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Suscripciones</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona las suscripciones de servicios de los clientes</p>
            </div>
            @can('subscription.create')
                <a href="{{ route('subscriptions.create') }}">
                    <x-button icon="plus">
                        Nueva Suscripción
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Stats Cards -->
        @if(isset($stats))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-secondary-500">Activas</p>
                            <p class="mt-1 text-3xl font-bold text-success-600">{{ $stats['active'] ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-success-100 rounded-full">
                            <x-icon name="check-circle" class="w-8 h-8 text-success-600" />
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-secondary-500">Pendientes</p>
                            <p class="mt-1 text-3xl font-bold text-warning-600">{{ $stats['pending'] ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-warning-100 rounded-full">
                            <x-icon name="clock" class="w-8 h-8 text-warning-600" />
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-secondary-500">Suspendidas</p>
                            <p class="mt-1 text-3xl font-bold text-danger-600">{{ $stats['suspended'] ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-danger-100 rounded-full">
                            <x-icon name="exclamation-circle" class="w-8 h-8 text-danger-600" />
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-secondary-500">Ingreso Mensual</p>
                            <p class="mt-1 text-3xl font-bold text-primary-600">${{ number_format($stats['monthly_revenue'] ?? 0, 2) }}</p>
                        </div>
                        <div class="p-3 bg-primary-100 rounded-full">
                            <x-icon name="currency-dollar" class="w-8 h-8 text-primary-600" />
                        </div>
                    </div>
                </x-card>
            </div>
        @endif

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('subscriptions.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Código, cliente..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="status" label="Estado" placeholder="Todos">
                        <option value="pending" {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="active" {{ ($filters['status'] ?? '') == 'active' ? 'selected' : '' }}>Activa</option>
                        <option value="suspended" {{ ($filters['status'] ?? '') == 'suspended' ? 'selected' : '' }}>Suspendida</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                    </x-select>

                    <x-select name="plan_id" label="Plan" placeholder="Todos">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ ($filters['plan_id'] ?? '') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="billing_cycle" label="Ciclo de Facturación" placeholder="Todos">
                        <option value="monthly" {{ ($filters['billing_cycle'] ?? '') == 'monthly' ? 'selected' : '' }}>Mensual</option>
                        <option value="quarterly" {{ ($filters['billing_cycle'] ?? '') == 'quarterly' ? 'selected' : '' }}>Trimestral</option>
                        <option value="semiannual" {{ ($filters['billing_cycle'] ?? '') == 'semiannual' ? 'selected' : '' }}>Semestral</option>
                        <option value="annual" {{ ($filters['billing_cycle'] ?? '') == 'annual' ? 'selected' : '' }}>Anual</option>
                    </x-select>

                    <x-input
                        type="number"
                        name="billing_day"
                        label="Día de Facturación"
                        placeholder="1-28"
                        :value="$filters['billing_day'] ?? ''"
                        min="1"
                        max="28"
                    />
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('subscriptions.index') }}">
                        <x-button type="button" variant="ghost">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
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
                        Cliente
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Plan
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Dirección de Servicio
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Facturación
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Precio Mensual
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-mono font-medium text-secondary-900">{{ $subscription->subscription_code }}</div>
                            <div class="text-xs text-secondary-500">{{ $subscription->created_at->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                    <x-icon name="user" class="w-4 h-4 text-primary-600" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-secondary-900">{{ $subscription->customer->name }}</div>
                                    <div class="text-xs text-secondary-500">{{ $subscription->customer->document_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">{{ $subscription->plan->name }}</div>
                            <div class="text-xs text-secondary-500">{{ $subscription->plan->download_speed }} / {{ $subscription->plan->upload_speed }} Mbps</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-secondary-900 max-w-xs truncate">{{ $subscription->serviceAddress->full_address ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($subscription->status === 'pending')
                                <x-badge variant="warning" dot>Pendiente</x-badge>
                            @elseif($subscription->status === 'active')
                                <x-badge variant="success" dot>Activa</x-badge>
                            @elseif($subscription->status === 'suspended')
                                <x-badge variant="danger" dot>Suspendida</x-badge>
                            @else
                                <x-badge variant="secondary" dot>Cancelada</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">Día {{ $subscription->billing_day }}</div>
                            <div class="text-xs text-secondary-500">
                                @if($subscription->billing_cycle === 'monthly')
                                    Mensual
                                @elseif($subscription->billing_cycle === 'quarterly')
                                    Trimestral
                                @elseif($subscription->billing_cycle === 'semiannual')
                                    Semestral
                                @else
                                    Anual
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-secondary-900">${{ number_format($subscription->monthly_price, 2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('subscriptions.show', $subscription) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('subscription.update')
                                    <a href="{{ route('subscriptions.edit', $subscription) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="document-text" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay suscripciones</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando una nueva suscripción para un cliente.</p>
                                @can('subscription.create')
                                    <div class="mt-4">
                                        <a href="{{ route('subscriptions.create') }}">
                                            <x-button icon="plus">Nueva Suscripción</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if($subscriptions->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $subscriptions->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
