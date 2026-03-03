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
        <!-- Header con Título y Botón Crear -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Clientes</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestión de clientes del sistema</p>
            </div>
            @can('crm.customer.create')
                <a href="{{ route('crm.customers.create') }}">
                    <x-button icon="plus">
                        Nuevo Cliente
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Estadísticas -->
        @if(isset($stats))
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-stat-card
                title="Total Clientes"
                :value="$stats['total'] ?? 0"
                icon="users"
                color="primary"
            />
            <x-stat-card
                title="Activos"
                :value="$stats['active'] ?? 0"
                icon="check-circle"
                color="success"
            />
            <x-stat-card
                title="Inactivos"
                :value="$stats['inactive'] ?? 0"
                icon="x-circle"
                color="danger"
            />
            <x-stat-card
                title="Este Mes"
                :value="$stats['this_month'] ?? 0"
                icon="calendar"
                color="info"
            />
        </div>
        @endif

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('crm.customers.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre, documento, teléfono..."
                        :value="request('search')"
                        icon="search"
                    />

                    <x-select name="is_active" label="Estado" placeholder="Todos">
                        <option value="">Todos</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </x-select>

                    <x-select name="customer_type" label="Tipo" placeholder="Todos">
                        <option value="">Todos los tipos</option>
                        <option value="personal" {{ request('customer_type') === 'personal' ? 'selected' : '' }}>Personal</option>
                        <option value="business" {{ request('customer_type') === 'business' ? 'selected' : '' }}>Empresa</option>
                    </x-select>

                    @if(isset($zones))
                    <x-select name="zone_id" label="Zona" placeholder="Todas">
                        <option value="">Todas las zonas</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </x-select>
                    @endif
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('crm.customers.index') }}">
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
                        Cliente
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Documento
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Contacto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Tipo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Fecha
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($customers as $customer)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <x-icon name="user" class="w-5 h-5 text-primary-600" />
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-secondary-900">{{ $customer->name }}</div>
                                    <div class="text-xs text-secondary-500">{{ $customer->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">{{ strtoupper($customer->document_type) }}</div>
                            <div class="text-xs text-secondary-500">{{ $customer->document_number }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">{{ $customer->phone }}</div>
                            @if($customer->email)
                                <div class="text-xs text-secondary-500">{{ $customer->email }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge variant="{{ $customer->customer_type === 'business' ? 'info' : 'default' }}" size="sm">
                                {{ $customer->customer_type === 'business' ? 'Empresa' : 'Personal' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$customer->is_active ? 'success' : 'danger'" dot>
                                {{ $customer->is_active ? 'Activo' : 'Inactivo' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                            {{ $customer->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('crm.customers.show', $customer) }}" class="text-secondary-600 hover:text-secondary-900" title="Ver detalle">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('crm.customer.update')
                                    <a href="{{ route('crm.customers.edit', $customer) }}" class="text-primary-600 hover:text-primary-900" title="Editar">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('crm.customer.delete')
                                    <form action="{{ route('crm.customers.destroy', $customer) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este cliente?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger-600 hover:text-danger-900" title="Eliminar">
                                            <x-icon name="trash" class="w-5 h-5" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="users" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay clientes</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo cliente.</p>
                                @can('crm.customer.create')
                                    <div class="mt-4">
                                        <a href="{{ route('crm.customers.create') }}">
                                            <x-button icon="plus">Nuevo Cliente</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            <!-- Paginación -->
            @if($customers->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $customers->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
