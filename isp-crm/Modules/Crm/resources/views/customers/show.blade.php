@extends('layouts.app')

@section('title', 'Detalle del Cliente')

@section('breadcrumb')
    <span class="text-secondary-500">CRM</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('crm.customers.index') }}" class="text-secondary-500 hover:text-secondary-700">Clientes</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $customer->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Acciones -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $customer->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge :variant="$customer->is_active ? 'success' : 'danger'" dot>
                        {{ $customer->is_active ? 'Activo' : 'Inactivo' }}
                    </x-badge>
                    <x-badge variant="{{ $customer->customer_type === 'business' ? 'info' : 'default' }}" size="sm">
                        {{ $customer->customer_type === 'business' ? 'Empresa' : 'Personal' }}
                    </x-badge>
                    <span class="text-xs text-secondary-500">{{ $customer->code }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                @can('crm.customer.update')
                    <a href="{{ route('crm.customers.edit', $customer) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
                @if($customer->is_active)
                    @can('crm.customer.update')
                        <form action="{{ route('crm.customers.deactivate', $customer) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit" variant="warning" icon="x-circle">Desactivar</x-button>
                        </form>
                    @endcan
                @else
                    @can('crm.customer.update')
                        <form action="{{ route('crm.customers.activate', $customer) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit" variant="success" icon="check-circle">Activar</x-button>
                        </form>
                    @endcan
                @endif
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
                            <dt class="text-sm font-medium text-secondary-500">Nombre / Razón Social</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->name }}</dd>
                        </div>
                        @if($customer->trade_name)
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nombre Comercial</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->trade_name }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipo de Cliente</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $customer->customer_type === 'business' ? 'Empresa' : 'Personal' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Documento</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ strtoupper($customer->document_type) }}: {{ $customer->document_number }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Teléfono</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                <a href="tel:{{ $customer->phone }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $customer->phone }}
                                </a>
                            </dd>
                        </div>
                        @if($customer->email)
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Correo Electrónico</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                <a href="mailto:{{ $customer->email }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $customer->email }}
                                </a>
                            </dd>
                        </div>
                        @endif
                        @if($customer->billing_email)
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Email de Facturación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                <a href="mailto:{{ $customer->billing_email }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $customer->billing_email }}
                                </a>
                            </dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Límite de Crédito</dt>
                            <dd class="mt-1 text-sm text-secondary-900">S/ {{ number_format($customer->credit_limit ?? 0, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Exento de Impuestos</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $customer->tax_exempt ? 'Sí' : 'No' }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Direcciones -->
                @if($customer->addresses && $customer->addresses->count() > 0)
                <x-card title="Direcciones">
                    <div class="space-y-4">
                        @foreach($customer->addresses as $address)
                            <div class="p-4 bg-secondary-50 rounded-lg">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-medium text-secondary-900">
                                                {{ $address->label ?? ucfirst($address->type) }}
                                            </h4>
                                            <x-badge variant="{{ $address->type === 'service' ? 'primary' : 'info' }}" size="sm">
                                                {{ ucfirst($address->type) }}
                                            </x-badge>
                                            @if($address->is_default)
                                                <x-badge variant="success" size="sm">Por defecto</x-badge>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-secondary-600">
                                            {{ $address->street }} {{ $address->number }}
                                            @if($address->floor), Piso {{ $address->floor }}@endif
                                            @if($address->apartment), Dpto. {{ $address->apartment }}@endif
                                        </p>
                                        <p class="text-sm text-secondary-600">
                                            {{ $address->district }}, {{ $address->city }}, {{ $address->province }}
                                            @if($address->postal_code) - {{ $address->postal_code }}@endif
                                        </p>
                                        @if($address->reference)
                                            <p class="mt-1 text-xs text-secondary-500">Ref: {{ $address->reference }}</p>
                                        @endif
                                        @if($address->zone)
                                            <p class="mt-1 text-xs text-secondary-500">Zona: {{ $address->zone->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
                @endif

                <!-- Contactos -->
                @if($customer->contacts && $customer->contacts->count() > 0)
                <x-card title="Contactos">
                    <div class="space-y-3">
                        @foreach($customer->contacts as $contact)
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                        <x-icon name="user" class="w-5 h-5 text-primary-600" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium text-secondary-900">{{ $contact->name }}</p>
                                            @if($contact->is_primary)
                                                <x-badge variant="primary" size="sm">Principal</x-badge>
                                            @endif
                                        </div>
                                        @if($contact->relationship)
                                            <p class="text-xs text-secondary-500">{{ $contact->relationship }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-secondary-900">{{ $contact->value }}</p>
                                    <p class="text-xs text-secondary-500">{{ ucfirst($contact->type) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
                @endif
            </div>

            <!-- Sidebar (1/3) -->
            <div class="space-y-6">
                <!-- Origen del Cliente -->
                @if($customer->lead)
                <x-card title="Origen del Cliente">
                    <x-alert variant="info">
                        Este cliente fue creado desde un prospecto (Lead).
                    </x-alert>
                    <div class="mt-3">
                        <a href="{{ route('crm.leads.show', $customer->lead) }}">
                            <x-button variant="outline" class="w-full" size="sm" icon="eye">
                                Ver Prospecto Original
                            </x-button>
                        </a>
                    </div>
                </x-card>
                @endif

                <!-- Información del Sistema -->
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">UUID</dt>
                            <dd class="mt-1 text-xs text-secondary-900 font-mono break-all">{{ $customer->uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Código</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $customer->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $customer->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Acciones -->
                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('crm.customer.update')
                            <a href="{{ route('crm.customers.edit', $customer) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar</x-button>
                            </a>
                        @endcan
                        <a href="#" @click.prevent="$dispatch('open-modal', 'add-address')" class="block">
                            <x-button variant="outline" class="w-full" icon="map-pin">Agregar Dirección</x-button>
                        </a>
                        <a href="#" @click.prevent="$dispatch('open-modal', 'add-contact')" class="block">
                            <x-button variant="outline" class="w-full" icon="user-plus">Agregar Contacto</x-button>
                        </a>
                        @can('crm.customer.delete')
                            <form action="{{ route('crm.customers.destroy', $customer) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar este cliente?')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                    Eliminar
                                </x-button>
                            </form>
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
