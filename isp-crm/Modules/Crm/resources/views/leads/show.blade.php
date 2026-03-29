@extends('layouts.app')

@section('title', 'Detalle del Prospecto')

@php
    $statusVariant = match ($lead->status->value) {
        'new' => 'info',
        'contacted' => 'primary',
        'qualified', 'proposal_sent', 'negotiating' => 'warning',
        'won' => 'success',
        'lost' => 'danger',
        default => 'default',
    };
    $latestFeasibilityRequest = $lead->feasibilityRequests->first();
@endphp

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
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $lead->name }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <x-badge :variant="$statusVariant" dot>{{ $lead->status->label() }}</x-badge>
                    <x-badge variant="default" size="sm">{{ $lead->source?->label() ?? 'Sin fuente' }}</x-badge>
                    @if($lead->is_duplicate)
                        <x-badge variant="warning" size="sm">Posible duplicado</x-badge>
                    @endif
                    @if($lead->isConverted())
                        <x-badge variant="success" size="sm">Convertido</x-badge>
                    @endif
                </div>
            </div>

            <div class="flex gap-2">
                @if(!$lead->isConverted())
                    <a href="{{ route('crm.leads.onboarding', $lead) }}">
                        <x-button variant="primary" icon="clipboard">Proceso de Alta</x-button>
                    </a>
                    <a href="{{ route('crm.leads.edit', $lead) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-card title="Contacto">
                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nombre</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Teléfono</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->phone }}</dd>
                        </div>
                        @if($lead->email)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Correo</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $lead->email }}</dd>
                            </div>
                        @endif
                        @if($lead->document_type && $lead->document_number)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Documento</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $lead->document_type->label() }} · {{ $lead->document_number }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                @if($lead->notes)
                    <x-card title="Notas">
                        <p class="whitespace-pre-wrap text-sm text-secondary-700">{{ $lead->notes }}</p>
                    </x-card>
                @endif

                @if($latestFeasibilityRequest && $latestFeasibilityRequest->latitude && $latestFeasibilityRequest->longitude)
                    <x-card title="Ubicacion Tecnica">
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <x-badge :variant="$latestFeasibilityRequest->status === 'confirmed' ? 'success' : ($latestFeasibilityRequest->status === 'rejected' ? 'danger' : 'warning')" size="sm">
                                {{ ucfirst($latestFeasibilityRequest->status) }}
                            </x-badge>
                            <span class="text-xs text-secondary-500">
                                Ultima solicitud: {{ $latestFeasibilityRequest->requested_at?->format('d/m/Y H:i') ?? '-' }}
                            </span>
                        </div>

                        <x-geo-point-picker
                            latitude-name="latitude"
                            longitude-name="longitude"
                            :latitude-value="$latestFeasibilityRequest->latitude"
                            :longitude-value="$latestFeasibilityRequest->longitude"
                            help="Ubicacion usada en la ultima evaluacion tecnica del lead."
                            height="18rem"
                            :readonly="true"
                            :show-inputs="false"
                        />
                    </x-card>
                @endif

                @if($lead->isConverted() && $lead->customer)
                    <x-card title="Resultado Comercial">
                        <p class="text-sm text-secondary-600">Este lead fue convertido el {{ $lead->converted_at->format('d/m/Y H:i') }}.</p>
                        <div class="mt-4">
                            <a href="{{ route('crm.customers.show', $lead->customer) }}">
                                <x-button icon="arrow-right">Abrir cliente</x-button>
                            </a>
                        </div>
                    </x-card>
                @endif
            </div>

            <div class="space-y-6">
                <x-card title="Resumen Comercial">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fuente</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->source?->label() ?? 'Sin fuente' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Estado</dt>
                            <dd class="mt-1">
                                <x-badge :variant="$statusVariant" dot>{{ $lead->status->label() }}</x-badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Zona</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->zone?->name ?? 'Sin zona' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Asignado a</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->assignedUser?->name ?? 'Sin asignar' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">UUID</dt>
                            <dd class="mt-1 break-all font-mono text-xs text-secondary-900">{{ $lead->uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Creado</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Actualizado</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Creado por</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $lead->createdByUser?->name ?? 'Sistema' }}</dd>
                        </div>
                    </dl>
                </x-card>
            </div>
        </div>
    </div>
@endsection
