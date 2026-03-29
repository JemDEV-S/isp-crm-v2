@extends('layouts.app')

@section('title', 'Clientes')

@section('breadcrumb')
    <span class="text-secondary-500">CRM</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Clientes</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Clientes</h1>
                <p class="mt-1 text-sm text-secondary-500">Base comercial consolidada con filtros por estado, tipo y zona.</p>
            </div>
            @can('crm.customer.create')
                <a href="{{ route('crm.customers.create') }}">
                    <x-button icon="plus">Nuevo Cliente</x-button>
                </a>
            @endcan
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-stat-card title="Total" :value="$stats['total'] ?? 0" icon="users" color="primary" />
            <x-stat-card title="Activos" :value="$stats['active'] ?? 0" icon="check-circle" color="success" />
            <x-stat-card title="Inactivos" :value="$stats['inactive'] ?? 0" icon="x-circle" color="danger" />
            <x-stat-card title="Empresas" :value="$stats['business'] ?? 0" icon="users" color="info" />
        </div>

        <x-card>
            <form method="GET" action="{{ route('crm.customers.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <x-input name="search" label="Buscar" placeholder="Nombre, código, documento..." :value="request('search')" icon="search" />

                    <x-select name="is_active" label="Estado">
                        <option value="">Todos</option>
                        <option value="1" @selected(request('is_active') === '1')>Activos</option>
                        <option value="0" @selected(request('is_active') === '0')>Inactivos</option>
                    </x-select>

                    <x-select name="customer_type" label="Tipo">
                        <option value="">Todos</option>
                        <option value="personal" @selected(request('customer_type') === 'personal')>Persona natural</option>
                        <option value="business" @selected(request('customer_type') === 'business')>Empresa</option>
                    </x-select>

                    <x-select name="zone_id" label="Zona">
                        <option value="">Todas</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected((string) request('zone_id') === (string) $zone->id)>{{ $zone->name }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('crm.customers.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Documento</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Contacto</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Alta</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-secondary-700">Acciones</th>
                </x-slot>

                @forelse($customers as $customer)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-secondary-900">{{ $customer->getDisplayName() }}</div>
                            <div class="text-xs text-secondary-500">{{ $customer->code }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">{{ $customer->document_type->label() }}</div>
                            <div class="text-xs text-secondary-500">{{ $customer->document_number }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-secondary-900">{{ $customer->phone }}</div>
                            @if($customer->email)
                                <div class="text-xs text-secondary-500">{{ $customer->email }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$customer->customer_type->value === 'business' ? 'info' : 'default'" size="sm">
                                {{ $customer->customer_type->label() }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$customer->is_active ? 'success' : 'danger'" dot>{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">{{ $customer->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('crm.customers.show', $customer) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="h-5 w-5" />
                                </a>
                                @can('crm.customer.update')
                                    <a href="{{ route('crm.customers.edit', $customer) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="h-5 w-5" />
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-secondary-500">No hay clientes registrados.</td>
                    </tr>
                @endforelse
            </x-table>

            @if($customers->hasPages())
                <div class="border-t border-secondary-200 px-6 py-4">
                    {{ $customers->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
