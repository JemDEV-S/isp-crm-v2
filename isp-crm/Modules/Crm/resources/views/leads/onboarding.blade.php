@extends('layouts.app')

@section('title', 'Proceso de Alta')

@section('breadcrumb')
    <span class="text-secondary-500">CRM</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('crm.leads.index') }}" class="text-secondary-500 hover:text-secondary-700">Prospectos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('crm.leads.show', $lead) }}" class="text-secondary-500 hover:text-secondary-700">{{ $lead->name }}</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Proceso de Alta</span>
@endsection

@php
    $serviceAddress = old('address', [
        'type' => 'service',
        'street' => '',
        'number' => '',
        'district' => '',
        'city' => '',
        'province' => '',
        'reference' => '',
        'address_reference' => '',
        'photo_url' => '',
        'latitude' => $latestFeasibilityRequest?->latitude,
        'longitude' => $latestFeasibilityRequest?->longitude,
        'zone_id' => $lead->zone_id,
    ]);
@endphp

@section('content')
    <div
        class="space-y-6"
        x-data="leadOnboarding({
            duplicateMatches: @js($onboarding['duplicate_matches'] ?? []),
            feasibilityRequest: @js($latestFeasibilityRequest?->toArray()),
            activeReservation: @js($activeReservation?->toArray()),
            endpoints: {
                duplicates: @js(route('crm.leads.check-duplicates', $lead)),
                feasibility: @js(route('crm.leads.feasibility', $lead)),
                reserve: @js(route('crm.leads.reserve-capacity', $lead)),
                showLead: @js(url('/crm/leads')),
            }
        })"
    >
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-secondary-900">Proceso de alta de cliente</h1>
                    @if($lead->converted_at)
                        <x-badge variant="success" icon="check-circle">Convertido</x-badge>
                    @elseif($lead->is_duplicate)
                        <x-badge variant="warning" icon="exclamation-triangle">Revision requerida</x-badge>
                    @else
                        <x-badge variant="info" icon="clipboard">En preparacion</x-badge>
                    @endif
                </div>
                <p class="mt-1 text-sm text-secondary-500">
                    Ejecute validaciones comerciales y tecnicas antes de convertir el prospecto a cliente.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('crm.leads.show', $lead) }}">
                    <x-button variant="ghost" icon="eye">Ver detalle</x-button>
                </a>
                @if(!$lead->converted_at)
                    <a href="{{ route('crm.leads.edit', $lead) }}">
                        <x-button variant="secondary" icon="pencil">Editar lead</x-button>
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-secondary-200 bg-white p-4 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">Lead</p>
                <p class="mt-2 text-lg font-semibold text-secondary-900">{{ $lead->name }}</p>
                <p class="mt-1 text-sm text-secondary-500">{{ $lead->phone }}{{ $lead->email ? ' - ' . $lead->email : '' }}</p>
            </div>
            <div class="rounded-xl border border-secondary-200 bg-white p-4 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">Duplicados</p>
                <p class="mt-2 text-lg font-semibold text-secondary-900" x-text="duplicateMatches.length"></p>
                <p class="mt-1 text-sm text-secondary-500">Coincidencias detectadas</p>
            </div>
            <div class="rounded-xl border border-secondary-200 bg-white p-4 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">Factibilidad</p>
                <p class="mt-2 text-lg font-semibold text-secondary-900" x-text="feasibilityLabel()"></p>
                <p class="mt-1 text-sm text-secondary-500">Ultima validacion tecnica</p>
            </div>
            <div class="rounded-xl border border-secondary-200 bg-white p-4 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">Reserva</p>
                <p class="mt-2 text-lg font-semibold text-secondary-900" x-text="reservationLabel()"></p>
                <p class="mt-1 text-sm text-secondary-500">Capacidad temporal asociada</p>
            </div>
        </div>

        <x-card>
            <x-slot name="header">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-secondary-900">Ruta de avance</h2>
                        <p class="mt-1 text-sm text-secondary-500">La conversion queda lista cuando no hay bloqueos comerciales y la validacion tecnica esta aprobada.</p>
                    </div>
                    <div class="text-sm font-medium text-secondary-600">
                        <span x-text="completedSteps()"></span> / 4 pasos listos
                    </div>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div class="rounded-xl border p-4 transition-colors" :class="stepClasses('lead')">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold">1. Datos base</p>
                        <x-icon name="check-circle" class="h-5 w-5" />
                    </div>
                    <p class="mt-2 text-sm">Lead registrado y listo para iniciar validaciones.</p>
                </div>
                <div class="rounded-xl border p-4 transition-colors" :class="stepClasses('duplicates')">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold">2. Duplicados</p>
                        <x-icon name="users" class="h-5 w-5" />
                    </div>
                    <p class="mt-2 text-sm">Debe quedar sin coincidencias pendientes.</p>
                </div>
                <div class="rounded-xl border p-4 transition-colors" :class="stepClasses('feasibility')">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold">3. Factibilidad</p>
                        <x-icon name="map-pin" class="h-5 w-5" />
                    </div>
                    <p class="mt-2 text-sm">Validacion de cobertura segun coordenadas o direccion georreferenciada.</p>
                </div>
                <div class="rounded-xl border p-4 transition-colors" :class="stepClasses('reservation')">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold">4. Reserva</p>
                        <x-icon name="clipboard" class="h-5 w-5" />
                    </div>
                    <p class="mt-2 text-sm">Reserva temporal del puerto NAP antes de la conversion.</p>
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <x-card title="Revision de duplicados" subtitle="Lance el analisis sobre el lead actual y valide si puede continuar.">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-2xl">
                            <div class="flex items-center gap-2">
                                <x-badge x-show="duplicateMatches.length === 0" variant="success" dot style="display: none;">Sin coincidencias</x-badge>
                                <x-badge x-show="duplicateMatches.length > 0" variant="warning" dot style="display: none;">Requiere revision</x-badge>
                                @if($lead->duplicateOf)
                                    <x-badge variant="warning" icon="exclamation-triangle">
                                        Marcado como duplicado de #{{ $lead->duplicateOf->id }}
                                    </x-badge>
                                @endif
                            </div>
                            <p class="mt-3 text-sm text-secondary-600">
                                El backend compara documento, telefono y correo. Si hay coincidencias, el lead debe resolverse antes de la conversion.
                            </p>
                        </div>

                        <x-button variant="secondary" icon="refresh" x-bind:disabled="loading.duplicates" @click="runDuplicateCheck()">
                            Validar duplicados
                        </x-button>
                    </div>

                    <div class="mt-4" x-show="feedback.duplicates" style="display: none;">
                        <x-alert variant="info" x-text="feedback.duplicates"></x-alert>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-lg border border-secondary-200" x-show="duplicateMatches.length > 0" style="display: none;">
                        <table class="min-w-full divide-y divide-secondary-200">
                            <thead class="bg-secondary-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Lead</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Coincidencia</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary-700">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 bg-white">
                                <template x-for="match in duplicateMatches" :key="match.lead_id">
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-secondary-900">
                                            <a class="font-medium text-primary-600 hover:text-primary-800" :href="`${endpoints.showLead}/${match.lead_id}`" x-text="`#${match.lead_id} ${match.name}`"></a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-secondary-600" x-text="match.matched_by"></td>
                                        <td class="px-4 py-3 text-sm text-secondary-600">
                                            <span x-text="match.status"></span>
                                            <span class="ml-1 text-xs text-success-600" x-show="match.is_converted">(convertido)</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-lg border border-dashed border-success-200 bg-success-50/70 p-4 text-sm text-success-800"
                         x-show="duplicateMatches.length === 0"
                         style="display: none;">
                        No se detectan conflictos activos. El flujo comercial puede continuar.
                    </div>
                </x-card>

                <x-card title="Factibilidad tecnica" subtitle="Use coordenadas del domicilio para consultar cobertura y disponibilidad de NAP.">
                    <form class="space-y-4" @submit.prevent="runFeasibilityCheck()">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <x-input name="latitude" label="Latitud" type="number" step="0.0000001" x-model="forms.feasibility.latitude" placeholder="-12.046374" required />
                            <x-input name="longitude" label="Longitud" type="number" step="0.0000001" x-model="forms.feasibility.longitude" placeholder="-77.042793" required />
                            <x-input name="radius_meters" label="Radio (m)" type="number" min="50" max="5000" x-model="forms.feasibility.radius_meters" />
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <x-button type="submit" icon="map-pin" x-bind:disabled="loading.feasibility">Consultar factibilidad</x-button>
                            <span class="text-sm text-secondary-500" x-show="feasibilityRequest?.requested_at" x-text="`Ultima solicitud: ${formatDate(feasibilityRequest.requested_at)}`"></span>
                        </div>
                    </form>

                    <div class="mt-4" x-show="feedback.feasibility" style="display: none;">
                        <x-alert variant="info" x-text="feedback.feasibility"></x-alert>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2" x-show="feasibilityRequest" style="display: none;">
                        <div class="rounded-xl border border-secondary-200 bg-secondary-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">Resultado</p>
                            <div class="mt-2 flex items-center gap-2">
                                <x-badge x-show="isFeasible()" variant="success" icon="check-circle" style="display: none;">Factible</x-badge>
                                <x-badge x-show="feasibilityRequest && !isFeasible()" variant="danger" icon="exclamation-circle" style="display: none;">No factible</x-badge>
                            </div>
                            <p class="mt-3 text-sm text-secondary-600" x-show="feasibilityResult()?.reason" x-text="feasibilityResult()?.reason"></p>
                            <p class="mt-3 text-sm text-secondary-600" x-show="feasibilityResult()?.distance_meters" x-text="`Distancia estimada: ${Math.round(feasibilityResult().distance_meters)} m`"></p>
                            <p class="mt-1 text-sm text-secondary-600" x-show="feasibilityResult()?.available_naps_count !== undefined" x-text="`NAPs detectados: ${feasibilityResult().available_naps_count}`"></p>
                        </div>

                        <div class="rounded-xl border border-secondary-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">NAP sugerido</p>
                            <template x-if="feasibilityResult()?.nearest_nap">
                                <div class="mt-2 space-y-2 text-sm text-secondary-700">
                                    <p><span class="font-medium text-secondary-900">Codigo:</span> <span x-text="feasibilityResult().nearest_nap.code"></span></p>
                                    <p><span class="font-medium text-secondary-900">Nombre:</span> <span x-text="feasibilityResult().nearest_nap.name"></span></p>
                                    <p><span class="font-medium text-secondary-900">Puertos libres:</span> <span x-text="feasibilityResult().nearest_nap.free_ports"></span></p>
                                </div>
                            </template>
                            <p class="mt-2 text-sm text-secondary-500" x-show="!feasibilityResult()?.nearest_nap">
                                La respuesta actual no trae detalle de puertos. Puede reservar manualmente indicando el <code>nap_port_id</code>.
                            </p>
                        </div>
                    </div>
                </x-card>

                <x-card title="Reserva temporal de capacidad" subtitle="El backend exige nap_port_id; mientras no se expongan puertos candidatos, la reserva es manual.">
                    <form class="space-y-4" @submit.prevent="reserveCapacity()">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <x-input name="nap_port_id" label="Puerto NAP" type="number" min="1" x-model="forms.reservation.nap_port_id" placeholder="ID del puerto" required />
                            <x-input name="hours" label="Horas" type="number" min="1" max="168" x-model="forms.reservation.hours" />
                            <x-input name="feasibility_request_id" label="Solicitud de factibilidad" type="number" x-model="forms.reservation.feasibility_request_id" placeholder="Opcional" />
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <x-button type="submit" variant="success" icon="clipboard" x-bind:disabled="loading.reservation">Reservar capacidad</x-button>
                            <span class="text-sm text-secondary-500" x-show="activeReservation?.expires_at" x-text="`Expira: ${formatDate(activeReservation.expires_at)}`"></span>
                        </div>
                    </form>

                    <div class="mt-4" x-show="feedback.reservation" style="display: none;">
                        <x-alert variant="info" x-text="feedback.reservation"></x-alert>
                    </div>

                    <div class="mt-4 rounded-xl border border-secondary-200 bg-secondary-50 p-4" x-show="activeReservation" style="display: none;">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">Estado</p>
                                <p class="mt-1 text-sm font-medium text-secondary-900" x-text="activeReservation.status"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">Puerto reservado</p>
                                <p class="mt-1 text-sm font-medium text-secondary-900" x-text="activeReservation.reservable_id"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">NAP</p>
                                <p class="mt-1 text-sm text-secondary-700" x-text="activeReservation.metadata?.nap_box_code || '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500">Puerto fisico</p>
                                <p class="mt-1 text-sm text-secondary-700" x-text="activeReservation.metadata?.port_number || '-'"></p>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Resumen comercial">
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="font-medium text-secondary-500">Documento</dt>
                            <dd class="mt-1 text-secondary-900">{{ $lead->document_type ? strtoupper($lead->document_type->value ?? (string) $lead->document_type) : '-' }} {{ $lead->document_number ?? '' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-secondary-500">Fuente</dt>
                            <dd class="mt-1 text-secondary-900">{{ $lead->source?->value ? ucfirst(str_replace('_', ' ', $lead->source->value)) : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-secondary-500">Zona</dt>
                            <dd class="mt-1 text-secondary-900">{{ $lead->zone?->name ?? 'Sin zona' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-secondary-500">Asignado a</dt>
                            <dd class="mt-1 text-secondary-900">{{ $lead->assignedUser?->name ?? 'Sin asignar' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Conversion a cliente" subtitle="Mantiene el POST estandar del modulo CRM para no abrir una segunda logica de negocio.">
                    @if($lead->converted_at && $lead->customer)
                        <x-alert variant="success" :dismissible="false">
                            Lead convertido el {{ $lead->converted_at->format('d/m/Y H:i') }}.
                        </x-alert>
                        <div class="mt-4">
                            <a href="{{ route('crm.customers.show', $lead->customer) }}">
                                <x-button class="w-full" icon="arrow-right">Abrir cliente</x-button>
                            </a>
                        </div>
                    @else
                        <form action="{{ route('crm.leads.convert', $lead) }}" method="POST" class="space-y-4">
                            @csrf

                            <x-select name="customer_type" label="Tipo de cliente" required>
                                <option value="personal" {{ old('customer_type', 'personal') === 'personal' ? 'selected' : '' }}>Personal</option>
                                <option value="business" {{ old('customer_type') === 'business' ? 'selected' : '' }}>Empresa</option>
                            </x-select>

                            <x-select name="document_type" label="Tipo de documento" required>
                                <option value="dni" {{ old('document_type', $lead->document_type?->value) === 'dni' ? 'selected' : '' }}>DNI</option>
                                <option value="ruc" {{ old('document_type', $lead->document_type?->value) === 'ruc' ? 'selected' : '' }}>RUC</option>
                                <option value="ce" {{ old('document_type', $lead->document_type?->value) === 'ce' ? 'selected' : '' }}>Carne de Extranjeria</option>
                                <option value="passport" {{ old('document_type', $lead->document_type?->value) === 'passport' ? 'selected' : '' }}>Pasaporte</option>
                            </x-select>

                            <x-input name="document_number" label="Numero de documento" :value="old('document_number', $lead->document_number)" required />
                            <x-input name="trade_name" label="Nombre comercial" :value="old('trade_name')" />
                            <x-input type="email" name="billing_email" label="Correo de facturacion" :value="old('billing_email', $lead->email)" />

                            <div class="rounded-xl border border-secondary-200 p-4">
                                <h3 class="text-sm font-semibold text-secondary-900">Direccion de servicio</h3>
                                <div class="mt-4 space-y-4">
                                    <input type="hidden" name="address[type]" value="service">
                                    <x-input name="address[street]" label="Calle" :value="$serviceAddress['street'] ?? ''" />

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <x-input name="address[number]" label="Numero" :value="$serviceAddress['number'] ?? ''" />
                                        <x-select name="address[zone_id]" label="Zona">
                                            <option value="">Seleccione...</option>
                                            @foreach($zones as $zone)
                                                <option value="{{ $zone->id }}" {{ (string) ($serviceAddress['zone_id'] ?? '') === (string) $zone->id ? 'selected' : '' }}>
                                                    {{ $zone->name }}
                                                </option>
                                            @endforeach
                                        </x-select>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <x-input name="address[district]" label="Distrito" :value="$serviceAddress['district'] ?? ''" />
                                        <x-input name="address[city]" label="Ciudad" :value="$serviceAddress['city'] ?? ''" />
                                        <x-input name="address[province]" label="Provincia" :value="$serviceAddress['province'] ?? ''" />
                                    </div>

                                    <x-input name="address[reference]" label="Referencia" :value="$serviceAddress['reference'] ?? ''" />
                                    <x-input name="address[address_reference]" label="Referencia extendida" :value="$serviceAddress['address_reference'] ?? ''" />
                                    <x-input name="address[photo_url]" label="URL de foto" :value="$serviceAddress['photo_url'] ?? ''" />

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <x-input type="number" step="0.0000001" name="address[latitude]" label="Latitud" :value="$serviceAddress['latitude'] ?? ''" />
                                        <x-input type="number" step="0.0000001" name="address[longitude]" label="Longitud" :value="$serviceAddress['longitude'] ?? ''" />
                                    </div>
                                </div>
                            </div>

                            <x-alert variant="warning" :dismissible="false" x-show="!canConvert()" style="display: none;">
                                Complete duplicados, factibilidad y reserva antes de convertir.
                            </x-alert>

                            <x-button type="submit" class="w-full" variant="success" icon="check-circle" x-bind:disabled="!canConvert()">
                                Convertir a cliente
                            </x-button>
                        </form>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function leadOnboarding(config) {
            return {
                duplicateMatches: config.duplicateMatches ?? [],
                feasibilityRequest: config.feasibilityRequest ?? null,
                activeReservation: config.activeReservation ?? null,
                loading: { duplicates: false, feasibility: false, reservation: false },
                feedback: { duplicates: '', feasibility: '', reservation: '' },
                forms: {
                    feasibility: {
                        latitude: config.feasibilityRequest?.latitude ?? '',
                        longitude: config.feasibilityRequest?.longitude ?? '',
                        radius_meters: config.feasibilityRequest?.radius_meters ?? 500,
                    },
                    reservation: {
                        nap_port_id: '',
                        hours: 24,
                        feasibility_request_id: config.feasibilityRequest?.id ?? '',
                    },
                },
                endpoints: config.endpoints,

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                },

                async post(url, payload = {}) {
                    const formData = new FormData();
                    Object.entries(payload).forEach(([key, value]) => {
                        if (value !== '' && value !== null && value !== undefined) {
                            formData.append(key, value);
                        }
                    });

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                        body: formData,
                    });

                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const validationMessage = data.errors ? Object.values(data.errors).flat().join(' ') : null;
                        throw new Error(validationMessage || data.message || 'No se pudo completar la operacion.');
                    }

                    return data;
                },

                async runDuplicateCheck() {
                    this.loading.duplicates = true;
                    this.feedback.duplicates = '';
                    try {
                        const response = await this.post(this.endpoints.duplicates);
                        this.duplicateMatches = response.data ?? [];
                        this.feedback.duplicates = this.duplicateMatches.length > 0
                            ? 'Se encontraron coincidencias que deben resolverse.'
                            : 'No se detectaron duplicados para este lead.';
                    } catch (error) {
                        this.feedback.duplicates = error.message;
                    } finally {
                        this.loading.duplicates = false;
                    }
                },

                async runFeasibilityCheck() {
                    this.loading.feasibility = true;
                    this.feedback.feasibility = '';
                    try {
                        const response = await this.post(this.endpoints.feasibility, this.forms.feasibility);
                        this.feasibilityRequest = response.data ?? null;
                        this.forms.reservation.feasibility_request_id = this.feasibilityRequest?.id ?? '';
                        this.feedback.feasibility = response.message || 'Factibilidad actualizada.';
                    } catch (error) {
                        this.feedback.feasibility = error.message;
                    } finally {
                        this.loading.feasibility = false;
                    }
                },

                async reserveCapacity() {
                    this.loading.reservation = true;
                    this.feedback.reservation = '';
                    try {
                        const response = await this.post(this.endpoints.reserve, this.forms.reservation);
                        this.activeReservation = response.data ?? null;
                        this.feedback.reservation = response.message || 'Reserva creada.';
                    } catch (error) {
                        this.feedback.reservation = error.message;
                    } finally {
                        this.loading.reservation = false;
                    }
                },

                feasibilityResult() {
                    return this.feasibilityRequest?.result_data ?? null;
                },

                isFeasible() {
                    return Boolean(this.feasibilityResult()?.is_feasible);
                },

                hasReservation() {
                    if (!this.activeReservation) {
                        return false;
                    }

                    if (!this.activeReservation.expires_at) {
                        return true;
                    }

                    return new Date(this.activeReservation.expires_at).getTime() > Date.now();
                },

                canConvert() {
                    return this.duplicateMatches.length === 0 && this.isFeasible() && this.hasReservation();
                },

                feasibilityLabel() {
                    if (!this.feasibilityRequest) {
                        return 'Pendiente';
                    }

                    return this.isFeasible() ? 'Factible' : 'No factible';
                },

                reservationLabel() {
                    return this.hasReservation() ? 'Activa' : 'Pendiente';
                },

                completedSteps() {
                    let completed = 1;

                    if (this.duplicateMatches.length === 0) {
                        completed++;
                    }

                    if (this.isFeasible()) {
                        completed++;
                    }

                    if (this.hasReservation()) {
                        completed++;
                    }

                    return completed;
                },

                stepClasses(step) {
                    const ready = {
                        lead: true,
                        duplicates: this.duplicateMatches.length === 0,
                        feasibility: this.isFeasible(),
                        reservation: this.hasReservation(),
                    };

                    return ready[step]
                        ? 'border-success-200 bg-success-50 text-success-900'
                        : 'border-secondary-200 bg-white text-secondary-700';
                },

                formatDate(value) {
                    if (!value) {
                        return '-';
                    }

                    return new Date(value).toLocaleString('es-PE', {
                        dateStyle: 'short',
                        timeStyle: 'short',
                    });
                },
            };
        }
    </script>
@endpush
