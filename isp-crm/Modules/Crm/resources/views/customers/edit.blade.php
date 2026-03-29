@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('breadcrumb')
    <span class="text-secondary-500">CRM</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('crm.customers.index') }}" class="text-secondary-500 hover:text-secondary-700">Clientes</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Editar Cliente</span>
@endsection

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Cliente</h1>
            <p class="mt-1 text-sm text-secondary-500">{{ $customer->getDisplayName() }} · {{ $customer->code }}</p>
        </div>

        <form action="{{ route('crm.customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            <x-card title="Identidad Comercial" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-select name="customer_type" label="Tipo de cliente" :error="$errors->first('customer_type')" required>
                        <option value="">Seleccione...</option>
                        @foreach($customerTypes as $type)
                            <option value="{{ $type['value'] }}" @selected(old('customer_type', $customer->customer_type->value) === $type['value'])>{{ $type['label'] }}</option>
                        @endforeach
                    </x-select>

                    <x-select name="document_type" label="Tipo de documento" :error="$errors->first('document_type')" required>
                        <option value="">Seleccione...</option>
                        @foreach($documentTypes as $type)
                            <option value="{{ $type['value'] }}" @selected(old('document_type', $customer->document_type->value) === $type['value'])>{{ $type['label'] }}</option>
                        @endforeach
                    </x-select>

                    <div class="md:col-span-2">
                        <x-input name="name" label="Nombre / razón social" :value="old('name', $customer->name)" :error="$errors->first('name')" required />
                    </div>

                    <x-input name="document_number" label="Número de documento" :value="old('document_number', $customer->document_number)" :error="$errors->first('document_number')" required />
                    <x-input name="trade_name" label="Nombre comercial" :value="old('trade_name', $customer->trade_name)" :error="$errors->first('trade_name')" />
                </div>
            </x-card>

            <x-card title="Datos de Contacto" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input type="tel" name="phone" label="Teléfono" :value="old('phone', $customer->phone)" :error="$errors->first('phone')" required icon="phone" />
                    <x-input type="email" name="email" label="Correo principal" :value="old('email', $customer->email)" :error="$errors->first('email')" icon="mail" />
                    <x-input type="email" name="billing_email" label="Correo de facturación" :value="old('billing_email', $customer->billing_email)" :error="$errors->first('billing_email')" />
                    <x-input type="number" step="0.01" name="credit_limit" label="Límite de crédito" :value="old('credit_limit', $customer->credit_limit)" :error="$errors->first('credit_limit')" />
                </div>
            </x-card>

            <x-card title="Configuración" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="tax_exempt" value="1" @checked(old('tax_exempt', $customer->tax_exempt)) class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Exento de impuestos</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active)) class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Cliente activo</span>
                    </label>
                </div>
            </x-card>

            <div class="flex justify-end gap-3">
                <a href="{{ route('crm.customers.show', $customer) }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
