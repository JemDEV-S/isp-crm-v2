@extends('layouts.app')

@section('title', 'Detalle del Prospecto')

@section('breadcrumb')
    <span class="text-secondary-500">CRM</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('crm.leads.index') }}" class="text-secondary-500 hover:text-secondary-700">Prospectos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $lead->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Acciones -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $lead->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    @php
                        $statusColors = [
                            'new' => 'info',
                            'contacted' => 'primary',
                            'qualified' => 'warning',
                            'proposal_sent' => 'warning',
                            'negotiating' => 'warning',
                            'won' => 'success',
                            'lost' => 'danger',
                        ];
                    @endphp
                    <x-badge :variant="$statusColors[$lead->status] ?? 'default'" dot>
                        {{ ucfirst(str_replace('_', ' ', $lead->status)) }}
                    </x-badge>
                    <x-badge variant="default" size="sm">
                        {{ ucfirst(str_replace('_', ' ', $lead->source)) }}
                    </x-badge>
                </div>
            </div>
            <div class="flex gap-2">
                @if(!$lead->converted_at)
                    @can('crm.lead.view')
                        <a href="{{ route('crm.leads.onboarding', $lead) }}">
                            <x-button variant="primary" icon="clipboard">Proceso de Alta</x-button>
                        </a>
                    @endcan
                    @can('crm.lead.convert')
                        <a href="#" @click.prevent="$dispatch('open-modal', 'convert-lead')">
                            <x-button variant="success" icon="check-circle">Convertir a Cliente</x-button>
                        </a>
                    @endcan
                    @can('crm.lead.update')
                        <a href="{{ route('crm.leads.edit', $lead) }}">
                            <x-button variant="secondary" icon="pencil">Editar</x-button>
                        </a>
                    @endcan
                @else
                    <x-badge variant="success" size="lg" icon="check-circle">
                        Convertido
                    </x-badge>
                @endif
            </div>
        </div>

        <!-- Layout de 2 columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal (2/3) -->
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Información de Contacto">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nombre Completo</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Teléfono</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                <a href="tel:{{ $lead->phone }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $lead->phone }}
                                </a>
                            </dd>
                        </div>
                        @if($lead->email)
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Correo Electrónico</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                <a href="mailto:{{ $lead->email }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $lead->email }}
                                </a>
                            </dd>
                        </div>
                        @endif
                        @if($lead->document_type && $lead->document_number)
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Documento</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ strtoupper($lead->document_type) }}: {{ $lead->document_number }}
                            </dd>
                        </div>
                        @endif
                    </dl>
                </x-card>

                @if($lead->notes)
                <x-card title="Notas">
                    <p class="text-sm text-secondary-700 whitespace-pre-wrap">{{ $lead->notes }}</p>
                </x-card>
                @endif

                @if($lead->converted_at && $lead->customer)
                <x-card title="Cliente Convertido">
                    <x-alert variant="success">
                        Este prospecto fue convertido a cliente el {{ $lead->converted_at->format('d/m/Y H:i') }}
                    </x-alert>
                    <div class="mt-4">
                        <a href="{{ route('crm.customers.show', $lead->customer) }}">
                            <x-button icon="arrow-right">Ver Cliente</x-button>
                        </a>
                    </div>
                </x-card>
                @endif
            </div>

            <!-- Sidebar (1/3) -->
            <div class="space-y-6">
                <x-card title="Información del Prospecto">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fuente</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ ucfirst(str_replace('_', ' ', $lead->source)) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Estado</dt>
                            <dd class="mt-1">
                                <x-badge :variant="$statusColors[$lead->status] ?? 'default'" dot>
                                    {{ ucfirst(str_replace('_', ' ', $lead->status)) }}
                                </x-badge>
                            </dd>
                        </div>
                        @if($lead->zone)
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Zona</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->zone->name }}</dd>
                        </div>
                        @endif
                        @if($lead->assignedUser)
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Asignado a</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->assignedUser->name }}</dd>
                        </div>
                        @endif
                    </dl>
                </x-card>

                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">UUID</dt>
                            <dd class="mt-1 text-xs text-secondary-900 font-mono break-all">{{ $lead->uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $lead->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $lead->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        @if($lead->createdByUser)
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Creado por</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->createdByUser->name }}</dd>
                        </div>
                        @endif
                    </dl>
                </x-card>

                @if(!$lead->converted_at)
                <x-card title="Acciones">
                    <div class="space-y-2">
                        <a href="{{ route('crm.leads.onboarding', $lead) }}" class="block">
                            <x-button variant="secondary" class="w-full" icon="clipboard">Abrir Proceso de Alta</x-button>
                        </a>
                        @can('crm.lead.update')
                            <a href="{{ route('crm.leads.edit', $lead) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar</x-button>
                            </a>
                        @endcan
                        @can('crm.lead.convert')
                            <a href="#" @click.prevent="$dispatch('open-modal', 'convert-lead')" class="block">
                                <x-button variant="success" class="w-full" icon="check-circle">
                                    Convertir a Cliente
                                </x-button>
                            </a>
                        @endcan
                        @can('crm.lead.delete')
                            <form action="{{ route('crm.leads.destroy', $lead) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar este prospecto?')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                    Eliminar
                                </x-button>
                            </form>
                        @endcan
                    </div>
                </x-card>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de Conversión -->
    @push('modals')
        <x-modal name="convert-lead" title="Convertir Prospecto a Cliente" maxWidth="2xl">
            <form action="{{ route('crm.leads.convert', $lead) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <x-alert variant="info">
                        Al convertir este prospecto se creará un nuevo cliente con la información proporcionada.
                    </x-alert>

                    <div class="grid grid-cols-2 gap-4">
                        <x-select name="customer_type" label="Tipo de Cliente" required>
                            <option value="personal">Personal</option>
                            <option value="business">Empresa</option>
                        </x-select>

                        <x-select name="document_type" label="Tipo de Documento" required>
                            <option value="dni">DNI</option>
                            <option value="ruc">RUC</option>
                            <option value="ce">Carné de Extranjería</option>
                            <option value="passport">Pasaporte</option>
                        </x-select>

                        <div class="col-span-2">
                            <x-input name="document_number" label="Número de Documento" required />
                        </div>

                        <div class="col-span-2">
                            <x-input name="trade_name" label="Nombre Comercial (Opcional)" />
                        </div>

                        <div class="col-span-2">
                            <x-input type="email" name="billing_email" label="Email de Facturación (Opcional)" />
                        </div>
                    </div>
                </div>

                <x-slot name="footer">
                    <x-button variant="ghost" type="button" @click="$dispatch('close-modal', 'convert-lead')">
                        Cancelar
                    </x-button>
                    <x-button type="submit" variant="success" icon="check-circle">
                        Convertir a Cliente
                    </x-button>
                </x-slot>
            </form>
        </x-modal>
    @endpush
@endsection
