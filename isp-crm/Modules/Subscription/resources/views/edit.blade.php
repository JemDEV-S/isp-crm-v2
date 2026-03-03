@extends('layouts.app')

@section('title', 'Editar Suscripción')

@section('breadcrumb')
    <span class="text-secondary-500">Servicios</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('subscriptions.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Suscripciones
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Editar</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Suscripción</h1>
            <p class="mt-1 text-sm text-secondary-500">
                {{ $subscription->subscription_code }} - {{ $subscription->customer->name }}
            </p>
        </div>

        <form action="{{ route('subscriptions.update', $subscription) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Información del Cliente y Plan -->
            <x-card title="Cliente y Plan" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Cliente</label>
                        <div class="p-3 bg-secondary-50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <x-icon name="user" class="w-5 h-5 text-secondary-600" />
                                <div>
                                    <div class="text-sm font-medium text-secondary-900">{{ $subscription->customer->name }}</div>
                                    <div class="text-xs text-secondary-500">{{ $subscription->customer->document_number }}</div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-secondary-500">El cliente no se puede cambiar una vez creada la suscripción</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Plan Actual</label>
                        <div class="p-3 bg-secondary-50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <x-icon name="server" class="w-5 h-5 text-secondary-600" />
                                <div>
                                    <div class="text-sm font-medium text-secondary-900">{{ $subscription->plan->name }}</div>
                                    <div class="text-xs text-secondary-500">
                                        {{ $subscription->plan->download_speed }} / {{ $subscription->plan->upload_speed }} Mbps -
                                        ${{ number_format($subscription->plan->monthly_price, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-secondary-500">
                            Para cambiar de plan, use la acción "Cambiar Plan" desde el detalle
                        </p>
                    </div>
                </div>
            </x-card>

            <!-- Dirección de Servicio -->
            <x-card title="Dirección de Instalación" class="mb-6">
                <x-select
                    name="service_address_id"
                    label="Dirección del Servicio"
                    placeholder="Seleccione una dirección..."
                    :error="$errors->first('service_address_id')"
                    required
                >
                    @foreach($addresses as $address)
                        <option value="{{ $address->id }}" {{ old('service_address_id', $subscription->service_address_id) == $address->id ? 'selected' : '' }}>
                            {{ $address->full_address }}
                        </option>
                    @endforeach
                </x-select>
            </x-card>

            <!-- Configuración de Facturación -->
            <x-card title="Configuración de Facturación" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-select
                        name="billing_cycle"
                        label="Ciclo de Facturación"
                        :error="$errors->first('billing_cycle')"
                        required
                    >
                        <option value="monthly" {{ old('billing_cycle', $subscription->billing_cycle) == 'monthly' ? 'selected' : '' }}>Mensual</option>
                        <option value="quarterly" {{ old('billing_cycle', $subscription->billing_cycle) == 'quarterly' ? 'selected' : '' }}>Trimestral</option>
                        <option value="semiannual" {{ old('billing_cycle', $subscription->billing_cycle) == 'semiannual' ? 'selected' : '' }}>Semestral</option>
                        <option value="annual" {{ old('billing_cycle', $subscription->billing_cycle) == 'annual' ? 'selected' : '' }}>Anual</option>
                    </x-select>

                    <x-input
                        type="number"
                        name="billing_day"
                        label="Día de Facturación"
                        placeholder="15"
                        :value="old('billing_day', $subscription->billing_day)"
                        :error="$errors->first('billing_day')"
                        min="1"
                        max="28"
                        required
                        hint="Día del mes (1-28)"
                    />

                    <x-input
                        type="date"
                        name="start_date"
                        label="Fecha de Inicio"
                        :value="old('start_date', $subscription->start_date?->format('Y-m-d'))"
                        :error="$errors->first('start_date')"
                        required
                    />
                </div>

                <div class="mt-4 p-4 bg-warning-50 border border-warning-200 rounded-lg">
                    <div class="flex gap-3">
                        <x-icon name="exclamation-triangle" class="w-5 h-5 text-warning-600 flex-shrink-0" />
                        <div class="text-sm text-warning-700">
                            <p class="font-medium">Advertencia</p>
                            <p class="mt-1">Cambiar el día de facturación afectará la próxima generación de facturas.</p>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Precios Personalizados -->
            <x-card title="Precios y Descuentos" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="number"
                        name="monthly_price"
                        label="Precio Mensual Personalizado"
                        placeholder="0.00"
                        :value="old('monthly_price', $subscription->monthly_price)"
                        :error="$errors->first('monthly_price')"
                        step="0.01"
                        min="0"
                        hint="Deje en blanco para usar precio del plan"
                    />

                    <x-input
                        type="number"
                        name="discount_percentage"
                        label="Descuento (%)"
                        placeholder="0"
                        :value="old('discount_percentage', $subscription->discount_percentage)"
                        :error="$errors->first('discount_percentage')"
                        step="0.01"
                        min="0"
                        max="100"
                        hint="Descuento permanente aplicado"
                    />
                </div>

                <div class="mt-4 p-4 bg-info-50 border border-info-200 rounded-lg">
                    <div class="flex gap-3">
                        <x-icon name="information-circle" class="w-5 h-5 text-info-600 flex-shrink-0" />
                        <div class="text-sm text-info-700">
                            <p>
                                Precio base del plan: <span class="font-semibold">${{ number_format($subscription->plan->monthly_price, 2) }}</span>
                            </p>
                            <p class="mt-1">
                                El precio personalizado reemplaza el precio base. El descuento se aplica sobre el precio final.
                            </p>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Estado -->
            <x-card title="Estado" class="mb-6">
                <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Estado Actual</label>
                    <div class="p-3 bg-secondary-50 rounded-lg">
                        @if($subscription->status === 'pending')
                            <x-badge variant="warning" size="lg">Pendiente</x-badge>
                        @elseif($subscription->status === 'active')
                            <x-badge variant="success" size="lg">Activa</x-badge>
                        @elseif($subscription->status === 'suspended')
                            <x-badge variant="danger" size="lg">Suspendida</x-badge>
                        @else
                            <x-badge variant="secondary" size="lg">Cancelada</x-badge>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-secondary-500">
                        Para cambiar el estado, use las acciones correspondientes desde el detalle de la suscripción
                    </p>
                </div>
            </x-card>

            <!-- Observaciones -->
            <x-card title="Observaciones" class="mb-6">
                <div>
                    <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                        Notas Internas
                    </label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="3"
                        class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Información adicional sobre la suscripción..."
                    >{{ old('notes', $subscription->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('subscriptions.show', $subscription) }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" variant="primary" icon="check">
                    Guardar Cambios
                </x-button>
            </div>
        </form>
    </div>
@endsection
