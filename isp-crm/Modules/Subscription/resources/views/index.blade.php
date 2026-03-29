@extends('layouts.app')

@section('title', 'Suscripciones')

@section('breadcrumb')
    <span class="text-secondary-500">Servicios</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Suscripciones</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Suscripciones</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona el ciclo comercial y operativo de cada servicio.</p>
            </div>
            @can('subscription.create')
                <a href="{{ route('subscriptions.create') }}">
                    <x-button icon="plus">Nueva Suscripción</x-button>
                </a>
            @endcan
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <x-card>
                <p class="text-sm font-medium text-secondary-500">Activas</p>
                <p class="mt-2 text-3xl font-bold text-success-600">{{ $stats['active'] ?? 0 }}</p>
            </x-card>
            <x-card>
                <p class="text-sm font-medium text-secondary-500">Pendientes de instalación</p>
                <p class="mt-2 text-3xl font-bold text-warning-600">{{ $stats['pending_installation'] ?? 0 }}</p>
            </x-card>
            <x-card>
                <p class="text-sm font-medium text-secondary-500">Suspendidas</p>
                <p class="mt-2 text-3xl font-bold text-danger-600">{{ $stats['suspended'] ?? 0 }}</p>
            </x-card>
            <x-card>
                <p class="text-sm font-medium text-secondary-500">Ingreso mensual aplicado</p>
                <p class="mt-2 text-3xl font-bold text-primary-600">S/ {{ number_format($stats['monthly_revenue'] ?? 0, 2) }}</p>
            </x-card>
        </div>

        <x-card>
            <form method="GET" action="{{ route('subscriptions.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Código o cliente"
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="status" label="Estado">
                        <option value="">Todos</option>
                        <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Borrador</option>
                        <option value="pending_installation" @selected(($filters['status'] ?? '') === 'pending_installation')>Pendiente instalación</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activa</option>
                        <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspendida</option>
                        <option value="suspended_voluntary" @selected(($filters['status'] ?? '') === 'suspended_voluntary')>Suspensión voluntaria</option>
                        <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelada</option>
                    </x-select>

                    <x-select name="plan_id" label="Plan">
                        <option value="">Todos</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) ($filters['plan_id'] ?? '') === (string) $plan->id)>
                                {{ $plan->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="billing_cycle" label="Ciclo">
                        <option value="">Todos</option>
                        <option value="monthly" @selected(($filters['billing_cycle'] ?? '') === 'monthly')>Mensual</option>
                        <option value="quarterly" @selected(($filters['billing_cycle'] ?? '') === 'quarterly')>Trimestral</option>
                        <option value="semiannual" @selected(($filters['billing_cycle'] ?? '') === 'semiannual')>Semestral</option>
                        <option value="annual" @selected(($filters['billing_cycle'] ?? '') === 'annual')>Anual</option>
                    </x-select>

                    <x-input
                        type="number"
                        name="billing_day"
                        label="Día facturación"
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

        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Código</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Dirección</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Facturación</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Mensualidad</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-secondary-700">Acciones</th>
                </x-slot>

                @forelse($subscriptions as $subscription)
                    @php($statusValue = $subscription->status->value)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-secondary-900">{{ $subscription->code }}</div>
                            <div class="text-xs text-secondary-500">{{ $subscription->created_at->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-secondary-900">{{ $subscription->customer->getDisplayName() }}</div>
                            <div class="text-xs text-secondary-500">{{ $subscription->customer->document_number }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-secondary-900">{{ $subscription->plan->name }}</div>
                            <div class="text-xs text-secondary-500">{{ $subscription->plan->download_speed }}/{{ $subscription->plan->upload_speed }} Mbps</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="max-w-xs truncate text-sm text-secondary-900">{{ $subscription->address?->getFullAddress() ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge
                                :variant="match ($statusValue) {
                                    'active' => 'success',
                                    'pending_installation' => 'warning',
                                    'suspended', 'suspended_voluntary' => 'danger',
                                    'cancelled', 'terminated' => 'secondary',
                                    default => 'secondary',
                                }"
                                dot
                            >
                                {{ $subscription->status->label() }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">Día {{ $subscription->billing_day }}</div>
                            <div class="text-xs text-secondary-500">
                                {{ match($subscription->billing_cycle->value) {
                                    'monthly' => 'Mensual',
                                    'quarterly' => 'Trimestral',
                                    'semiannual' => 'Semestral',
                                    'annual' => 'Anual',
                                    default => ucfirst($subscription->billing_cycle->value),
                                } }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-secondary-900">S/ {{ number_format($subscription->monthly_price, 2) }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('subscriptions.show', $subscription) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="h-5 w-5" />
                                </a>
                                @can('subscription.update')
                                    <a href="{{ route('subscriptions.edit', $subscription) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="h-5 w-5" />
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-sm text-secondary-500">
                            No hay suscripciones registradas todavía.
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if($subscriptions->hasPages())
                <div class="border-t border-secondary-200 px-6 py-4">
                    {{ $subscriptions->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
