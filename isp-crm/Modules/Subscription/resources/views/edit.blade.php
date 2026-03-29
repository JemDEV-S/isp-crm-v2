@extends('layouts.app')

@section('title', 'Editar Suscripción')

@section('breadcrumb')
    <span class="text-secondary-500">Servicios</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('subscriptions.index') }}" class="text-secondary-500 hover:text-secondary-700">Suscripciones</a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Editar</span>
@endsection

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Suscripción</h1>
            <p class="mt-1 text-sm text-secondary-500">{{ $subscription->code }} - {{ $subscription->customer->getDisplayName() }}</p>
        </div>

        <form action="{{ route('subscriptions.update', $subscription) }}" method="POST">
            @csrf
            @method('PUT')

            <x-card title="Cliente y Plan" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-secondary-50 p-4">
                        <p class="text-sm font-medium text-secondary-500">Cliente</p>
                        <p class="mt-2 font-semibold text-secondary-900">{{ $subscription->customer->getDisplayName() }}</p>
                        <p class="text-sm text-secondary-500">{{ $subscription->customer->document_number }}</p>
                    </div>
                    <div class="rounded-lg bg-secondary-50 p-4">
                        <p class="text-sm font-medium text-secondary-500">Plan actual</p>
                        <p class="mt-2 font-semibold text-secondary-900">{{ $subscription->plan->name }}</p>
                        <p class="text-sm text-secondary-500">
                            {{ $subscription->plan->download_speed }}/{{ $subscription->plan->upload_speed }} Mbps | S/ {{ number_format($subscription->plan->price, 2) }}
                        </p>
                    </div>
                </div>
            </x-card>

            <x-card title="Dirección y Facturación" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-select
                        name="service_address_id"
                        label="Dirección del servicio"
                        :error="$errors->first('service_address_id')"
                        required
                    >
                        @foreach($addresses as $address)
                            <option value="{{ $address->id }}" @selected((string) old('service_address_id', $subscription->address_id) === (string) $address->id)>
                                {{ $address->getFullAddress() }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="billing_cycle" label="Ciclo" :error="$errors->first('billing_cycle')" required>
                        <option value="monthly" @selected(old('billing_cycle', $subscription->billing_cycle->value) === 'monthly')>Mensual</option>
                        <option value="quarterly" @selected(old('billing_cycle', $subscription->billing_cycle->value) === 'quarterly')>Trimestral</option>
                        <option value="semiannual" @selected(old('billing_cycle', $subscription->billing_cycle->value) === 'semiannual')>Semestral</option>
                        <option value="annual" @selected(old('billing_cycle', $subscription->billing_cycle->value) === 'annual')>Anual</option>
                    </x-select>

                    <x-input
                        type="number"
                        name="billing_day"
                        label="Día facturación"
                        :value="old('billing_day', $subscription->billing_day)"
                        :error="$errors->first('billing_day')"
                        min="1"
                        max="28"
                        required
                    />

                    <x-input
                        type="date"
                        name="start_date"
                        label="Inicio"
                        :value="old('start_date', $subscription->start_date?->format('Y-m-d'))"
                        :error="$errors->first('start_date')"
                        required
                    />

                    <x-input
                        type="number"
                        name="contracted_months"
                        label="Permanencia"
                        :value="old('contracted_months', $subscription->contracted_months)"
                        :error="$errors->first('contracted_months')"
                        min="1"
                        max="60"
                    />
                </div>
            </x-card>

            <x-card title="Precio Aplicado" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-input
                        type="number"
                        step="0.01"
                        name="monthly_price"
                        label="Mensualidad de la suscripción"
                        :value="old('monthly_price', $subscription->monthly_price)"
                        :error="$errors->first('monthly_price')"
                        min="0"
                    />

                    <x-input
                        type="number"
                        step="0.01"
                        name="discount_percentage"
                        label="Descuento (%)"
                        :value="old('discount_percentage', $subscription->discount_percentage)"
                        :error="$errors->first('discount_percentage')"
                        min="0"
                        max="100"
                    />
                </div>
                <p class="mt-3 text-xs text-secondary-500">Precio base del plan: S/ {{ number_format($subscription->plan->price, 2) }}. Instalación pactada: S/ {{ number_format($subscription->installation_fee, 2) }}.</p>
            </x-card>

            <x-card title="Estado y Notas" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-secondary-50 p-4">
                        <p class="text-sm font-medium text-secondary-500">Estado actual</p>
                        <div class="mt-2">
                            <x-badge
                                :variant="match ($subscription->status->value) {
                                    'active' => 'success',
                                    'pending_installation' => 'warning',
                                    'suspended', 'suspended_voluntary' => 'danger',
                                    default => 'secondary',
                                }"
                            >
                                {{ $subscription->status->label() }}
                            </x-badge>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="mb-1 block text-sm font-medium text-secondary-700">Notas internas</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="block w-full rounded-lg border-secondary-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >{{ old('notes', $subscription->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <div class="sticky bottom-0 mt-8 flex items-center justify-end gap-3 border-t border-secondary-200 bg-white/80 py-4 backdrop-blur">
                <a href="{{ route('subscriptions.show', $subscription) }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
