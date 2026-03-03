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
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Cliente</h1>
            <p class="mt-1 text-sm text-secondary-500">Modifique la información del cliente</p>
        </div>

        <!-- Formulario -->
        <form action="{{ route('crm.customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Sección 1: Tipo de Cliente -->
            <x-card title="Tipo de Cliente" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-select
                        name="customer_type"
                        label="Tipo de Cliente"
                        :error="$errors->first('customer_type')"
                        required
                    >
                        <option value="">Seleccione...</option>
                        <option value="personal" {{ old('customer_type', $customer->customer_type) === 'personal' ? 'selected' : '' }}>Personal</option>
                        <option value="business" {{ old('customer_type', $customer->customer_type) === 'business' ? 'selected' : '' }}>Empresa</option>
                    </x-select>

                    <x-select
                        name="document_type"
                        label="Tipo de Documento"
                        :error="$errors->first('document_type')"
                        required
                    >
                        <option value="">Seleccione...</option>
                        <option value="dni" {{ old('document_type', $customer->document_type) === 'dni' ? 'selected' : '' }}>DNI</option>
                        <option value="ruc" {{ old('document_type', $customer->document_type) === 'ruc' ? 'selected' : '' }}>RUC</option>
                        <option value="ce" {{ old('document_type', $customer->document_type) === 'ce' ? 'selected' : '' }}>Carné de Extranjería</option>
                        <option value="passport" {{ old('document_type', $customer->document_type) === 'passport' ? 'selected' : '' }}>Pasaporte</option>
                    </x-select>
                </div>
            </x-card>

            <!-- Sección 2: Información Personal/Comercial -->
            <x-card title="Información del Cliente" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input
                            name="name"
                            label="Nombre / Razón Social"
                            :value="old('name', $customer->name)"
                            :error="$errors->first('name')"
                            required
                            placeholder="Nombre completo o razón social"
                        />
                    </div>

                    <x-input
                        name="document_number"
                        label="Número de Documento"
                        :value="old('document_number', $customer->document_number)"
                        :error="$errors->first('document_number')"
                        required
                        placeholder="Número de documento"
                    />

                    <x-input
                        name="trade_name"
                        label="Nombre Comercial"
                        :value="old('trade_name', $customer->trade_name)"
                        :error="$errors->first('trade_name')"
                        placeholder="Solo para empresas"
                    />

                    <x-input
                        type="tel"
                        name="phone"
                        label="Teléfono"
                        :value="old('phone', $customer->phone)"
                        :error="$errors->first('phone')"
                        required
                        placeholder="999 999 999"
                        icon="phone"
                    />

                    <x-input
                        type="email"
                        name="email"
                        label="Correo Electrónico"
                        :value="old('email', $customer->email)"
                        :error="$errors->first('email')"
                        placeholder="ejemplo@correo.com"
                        icon="mail"
                    />

                    <x-input
                        type="email"
                        name="billing_email"
                        label="Email de Facturación"
                        :value="old('billing_email', $customer->billing_email)"
                        :error="$errors->first('billing_email')"
                        placeholder="facturacion@correo.com"
                    />

                    <x-input
                        type="number"
                        name="credit_limit"
                        label="Límite de Crédito"
                        :value="old('credit_limit', $customer->credit_limit)"
                        :error="$errors->first('credit_limit')"
                        placeholder="0.00"
                        step="0.01"
                    />
                </div>
            </x-card>

            <!-- Sección 3: Configuración Adicional -->
            <x-card title="Configuración Adicional" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="tax_exempt" value="1" {{ old('tax_exempt', $customer->tax_exempt) ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Exento de impuestos</span>
                        </label>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Cliente activo</span>
                        </label>
                    </div>
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('crm.customers.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
