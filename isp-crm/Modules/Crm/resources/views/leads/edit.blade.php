@extends('layouts.app')

@section('title', 'Editar Prospecto')

@section('breadcrumb')
    <span class="text-secondary-500">CRM</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('crm.leads.index') }}" class="text-secondary-500 hover:text-secondary-700">Prospectos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Editar Prospecto</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Prospecto</h1>
            <p class="mt-1 text-sm text-secondary-500">Modifique la información del prospecto</p>
        </div>

        <!-- Formulario -->
        <form action="{{ route('crm.leads.update', $lead) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Sección 1: Información Personal -->
            <x-card title="Información Personal" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input
                            name="name"
                            label="Nombre Completo"
                            :value="old('name', $lead->name)"
                            :error="$errors->first('name')"
                            required
                            placeholder="Ingrese el nombre completo"
                        />
                    </div>

                    <x-input
                        type="tel"
                        name="phone"
                        label="Teléfono"
                        :value="old('phone', $lead->phone)"
                        :error="$errors->first('phone')"
                        required
                        placeholder="999 999 999"
                        icon="phone"
                    />

                    <x-input
                        type="email"
                        name="email"
                        label="Correo Electrónico"
                        :value="old('email', $lead->email)"
                        :error="$errors->first('email')"
                        placeholder="ejemplo@correo.com"
                        icon="mail"
                    />

                    <x-select
                        name="document_type"
                        label="Tipo de Documento"
                        :error="$errors->first('document_type')"
                    >
                        <option value="">Seleccione...</option>
                        <option value="dni" {{ old('document_type', $lead->document_type) === 'dni' ? 'selected' : '' }}>DNI</option>
                        <option value="ruc" {{ old('document_type', $lead->document_type) === 'ruc' ? 'selected' : '' }}>RUC</option>
                        <option value="ce" {{ old('document_type', $lead->document_type) === 'ce' ? 'selected' : '' }}>Carné de Extranjería</option>
                        <option value="passport" {{ old('document_type', $lead->document_type) === 'passport' ? 'selected' : '' }}>Pasaporte</option>
                    </x-select>

                    <x-input
                        name="document_number"
                        label="Número de Documento"
                        :value="old('document_number', $lead->document_number)"
                        :error="$errors->first('document_number')"
                        placeholder="Número de documento"
                    />
                </div>
            </x-card>

            <!-- Sección 2: Información del Prospecto -->
            <x-card title="Información del Prospecto" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-select
                        name="source"
                        label="Fuente"
                        :error="$errors->first('source')"
                    >
                        <option value="">Seleccione...</option>
                        <option value="walk_in" {{ old('source', $lead->source) === 'walk_in' ? 'selected' : '' }}>Visita Directa</option>
                        <option value="phone" {{ old('source', $lead->source) === 'phone' ? 'selected' : '' }}>Teléfono</option>
                        <option value="website" {{ old('source', $lead->source) === 'website' ? 'selected' : '' }}>Sitio Web</option>
                        <option value="referral" {{ old('source', $lead->source) === 'referral' ? 'selected' : '' }}>Referido</option>
                        <option value="social_media" {{ old('source', $lead->source) === 'social_media' ? 'selected' : '' }}>Redes Sociales</option>
                        <option value="campaign" {{ old('source', $lead->source) === 'campaign' ? 'selected' : '' }}>Campaña</option>
                    </x-select>

                    <x-select
                        name="status"
                        label="Estado"
                        :error="$errors->first('status')"
                    >
                        <option value="new" {{ old('status', $lead->status) === 'new' ? 'selected' : '' }}>Nuevo</option>
                        <option value="contacted" {{ old('status', $lead->status) === 'contacted' ? 'selected' : '' }}>Contactado</option>
                        <option value="qualified" {{ old('status', $lead->status) === 'qualified' ? 'selected' : '' }}>Calificado</option>
                        <option value="proposal_sent" {{ old('status', $lead->status) === 'proposal_sent' ? 'selected' : '' }}>Propuesta Enviada</option>
                        <option value="negotiating" {{ old('status', $lead->status) === 'negotiating' ? 'selected' : '' }}>Negociando</option>
                        <option value="won" {{ old('status', $lead->status) === 'won' ? 'selected' : '' }}>Ganado</option>
                        <option value="lost" {{ old('status', $lead->status) === 'lost' ? 'selected' : '' }}>Perdido</option>
                    </x-select>

                    @if(isset($zones) && $zones->count() > 0)
                    <x-select
                        name="zone_id"
                        label="Zona"
                        :error="$errors->first('zone_id')"
                    >
                        <option value="">Seleccione...</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ old('zone_id', $lead->zone_id) == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </x-select>
                    @endif

                    @if(isset($users) && $users->count() > 0)
                    <x-select
                        name="assigned_to"
                        label="Asignar a"
                        :error="$errors->first('assigned_to')"
                    >
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to', $lead->assigned_to) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </x-select>
                    @endif

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                            Notas
                        </label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="3"
                            class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Observaciones o información adicional..."
                        >{{ old('notes', $lead->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('crm.leads.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
