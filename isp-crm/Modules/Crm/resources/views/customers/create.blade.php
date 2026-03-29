@extends('layouts.app')

@section('title', 'Crear Cliente')

@section('breadcrumb')
    <span class="text-secondary-500">CRM</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('crm.customers.index') }}" class="text-secondary-500 hover:text-secondary-700">Clientes</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Crear Cliente</span>
@endsection

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Nuevo Cliente</h1>
            <p class="mt-1 text-sm text-secondary-500">Captura datos base y, si ya los tienes, deja lista la dirección de servicio y el contacto principal.</p>
        </div>

        <form action="{{ route('crm.customers.store') }}" method="POST">
            @csrf

            <x-card title="Identidad Comercial" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-select name="customer_type" label="Tipo de cliente" :error="$errors->first('customer_type')" required>
                        <option value="">Seleccione...</option>
                        @foreach($customerTypes as $type)
                            <option value="{{ $type['value'] }}" @selected(old('customer_type') === $type['value'])>{{ $type['label'] }}</option>
                        @endforeach
                    </x-select>

                    <x-select name="document_type" label="Tipo de documento" :error="$errors->first('document_type')" required>
                        <option value="">Seleccione...</option>
                        @foreach($documentTypes as $type)
                            <option value="{{ $type['value'] }}" @selected(old('document_type') === $type['value'])>{{ $type['label'] }}</option>
                        @endforeach
                    </x-select>

                    <div class="md:col-span-2">
                        <x-input
                            name="name"
                            label="Nombre / razón social"
                            :value="old('name')"
                            :error="$errors->first('name')"
                            required
                        />
                    </div>

                    <x-input
                        name="document_number"
                        label="Número de documento"
                        :value="old('document_number')"
                        :error="$errors->first('document_number')"
                        required
                    />

                    <x-input
                        name="trade_name"
                        label="Nombre comercial"
                        :value="old('trade_name')"
                        :error="$errors->first('trade_name')"
                        hint="Útil para clientes empresa"
                    />
                </div>
            </x-card>

            <x-card title="Datos de Contacto" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input type="tel" name="phone" label="Teléfono" :value="old('phone')" :error="$errors->first('phone')" required icon="phone" />
                    <x-input type="email" name="email" label="Correo principal" :value="old('email')" :error="$errors->first('email')" icon="mail" />
                    <x-input type="email" name="billing_email" label="Correo de facturación" :value="old('billing_email')" :error="$errors->first('billing_email')" />
                    <x-input type="number" step="0.01" name="credit_limit" label="Límite de crédito" :value="old('credit_limit', 0)" :error="$errors->first('credit_limit')" />
                </div>
                <div class="mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="tax_exempt" value="1" @checked(old('tax_exempt')) class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Exento de impuestos</span>
                    </label>
                </div>
            </x-card>

            <x-card title="Dirección Inicial de Servicio" class="mb-6">
                <p class="mb-4 text-sm text-secondary-500">Opcional. Si la venta ya está avanzada, conviene crearla desde aquí para no romper el flujo comercial.</p>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input name="service_address[label]" label="Etiqueta" :value="old('service_address.label')" :error="$errors->first('service_address.label')" placeholder="Casa principal, oficina, sede..." />
                    <x-select name="service_address[zone_id]" label="Zona" :error="$errors->first('service_address.zone_id')">
                        <option value="">Seleccione...</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected((string) old('service_address.zone_id') === (string) $zone->id)>{{ $zone->name }}</option>
                        @endforeach
                    </x-select>
                    <div class="md:col-span-2">
                        <x-input name="service_address[street]" label="Calle / vía" :value="old('service_address.street')" :error="$errors->first('service_address.street')" />
                    </div>
                    <x-input name="service_address[number]" label="Número" :value="old('service_address.number')" :error="$errors->first('service_address.number')" />
                    <x-input name="service_address[reference]" label="Referencia" :value="old('service_address.reference')" :error="$errors->first('service_address.reference')" />
                    <x-input name="service_address[district]" label="Distrito" :value="old('service_address.district')" :error="$errors->first('service_address.district')" />
                    <x-input name="service_address[city]" label="Ciudad" :value="old('service_address.city')" :error="$errors->first('service_address.city')" />
                    <x-input name="service_address[province]" label="Provincia" :value="old('service_address.province')" :error="$errors->first('service_address.province')" />
                    <x-input name="service_address[postal_code]" label="Código postal" :value="old('service_address.postal_code')" :error="$errors->first('service_address.postal_code')" />
                </div>
            </x-card>

            <x-card title="Contacto Principal Adicional" class="mb-6">
                <p class="mb-4 text-sm text-secondary-500">Opcional. Útil cuando quien contrata no es la misma persona que atiende la instalación o la cobranza.</p>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input name="primary_contact[name]" label="Nombre" :value="old('primary_contact.name')" :error="$errors->first('primary_contact.name')" />
                    <x-input name="primary_contact[relationship]" label="Relación / cargo" :value="old('primary_contact.relationship')" :error="$errors->first('primary_contact.relationship')" />
                    <x-select name="primary_contact[type]" label="Canal" :error="$errors->first('primary_contact.type')">
                        <option value="">Seleccione...</option>
                        @foreach($contactTypes as $type)
                            <option value="{{ $type['value'] }}" @selected(old('primary_contact.type') === $type['value'])>{{ $type['label'] }}</option>
                        @endforeach
                    </x-select>
                    <x-input name="primary_contact[value]" label="Dato de contacto" :value="old('primary_contact.value')" :error="$errors->first('primary_contact.value')" />
                </div>
                <div class="mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="primary_contact[receives_notifications]" value="1" @checked(old('primary_contact.receives_notifications')) class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Recibe notificaciones</span>
                    </label>
                </div>
            </x-card>

            <div class="flex justify-end gap-3">
                <a href="{{ route('crm.customers.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Crear Cliente</x-button>
            </div>
        </form>
    </div>
@endsection
