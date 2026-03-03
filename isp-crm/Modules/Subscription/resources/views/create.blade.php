@extends('layouts.app')

@section('title', 'Nueva Suscripción')

@section('breadcrumb')
    <span class="text-secondary-500">Servicios</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('subscriptions.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Suscripciones
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Nueva Suscripción</span>
@endsection

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Nueva Suscripción</h1>
            <p class="mt-1 text-sm text-secondary-500">
                Registre una nueva suscripción de servicio para un cliente.
            </p>
        </div>

        <form action="{{ route('subscriptions.store') }}" method="POST" x-data="subscriptionForm">
            @csrf

            <!-- Cliente y Plan -->
            <x-card title="Cliente y Plan de Servicio" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-select
                            name="customer_id"
                            label="Cliente"
                            placeholder="Seleccione un cliente..."
                            :error="$errors->first('customer_id')"
                            required
                            x-model="customerId"
                            @change="loadCustomerAddresses()"
                        >
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} - {{ $customer->document_number }}
                                </option>
                            @endforeach
                        </x-select>
                        <p class="mt-1 text-xs text-secondary-500">Seleccione el cliente que contratará el servicio</p>
                    </div>

                    <div>
                        <x-select
                            name="plan_id"
                            label="Plan de Servicio"
                            placeholder="Seleccione un plan..."
                            :error="$errors->first('plan_id')"
                            required
                            x-model="planId"
                            @change="updatePlanDetails()"
                        >
                            @foreach($plans as $plan)
                                <option
                                    value="{{ $plan->id }}"
                                    data-price="{{ $plan->monthly_price }}"
                                    data-download="{{ $plan->download_speed }}"
                                    data-upload="{{ $plan->upload_speed }}"
                                    {{ old('plan_id') == $plan->id ? 'selected' : '' }}
                                >
                                    {{ $plan->name }} - ${{ number_format($plan->monthly_price, 2) }}
                                </option>
                            @endforeach
                        </x-select>
                        <p class="mt-1 text-xs text-secondary-500">Plan de velocidad y precio</p>
                    </div>
                </div>

                <div x-show="planId" class="mt-4 p-4 bg-primary-50 border border-primary-200 rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-icon name="information-circle" class="w-5 h-5 text-primary-600 flex-shrink-0" />
                        <div class="text-sm text-primary-700">
                            <p class="font-medium">Plan seleccionado</p>
                            <p class="mt-1">
                                Velocidad: <span x-text="planDetails.download"></span> / <span x-text="planDetails.upload"></span> Mbps |
                                Precio base: $<span x-text="planDetails.price"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Dirección de Servicio -->
            <x-card title="Dirección de Instalación" class="mb-6">
                <div class="grid grid-cols-1 gap-6">
                    <x-select
                        name="service_address_id"
                        label="Dirección del Servicio"
                        placeholder="Seleccione una dirección..."
                        :error="$errors->first('service_address_id')"
                        required
                    >
                        @foreach($addresses as $address)
                            <option value="{{ $address->id }}" {{ old('service_address_id') == $address->id ? 'selected' : '' }}>
                                {{ $address->full_address }}
                            </option>
                        @endforeach
                    </x-select>
                    <p class="mt-1 text-xs text-secondary-500">
                        Dirección donde se instalará el servicio. Si no encuentra la dirección, créela primero desde el módulo de Clientes.
                    </p>
                </div>
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
                        <option value="monthly" {{ old('billing_cycle', 'monthly') == 'monthly' ? 'selected' : '' }}>Mensual</option>
                        <option value="quarterly" {{ old('billing_cycle') == 'quarterly' ? 'selected' : '' }}>Trimestral</option>
                        <option value="semiannual" {{ old('billing_cycle') == 'semiannual' ? 'selected' : '' }}>Semestral</option>
                        <option value="annual" {{ old('billing_cycle') == 'annual' ? 'selected' : '' }}>Anual</option>
                    </x-select>

                    <x-input
                        type="number"
                        name="billing_day"
                        label="Día de Facturación"
                        placeholder="15"
                        :value="old('billing_day', 1)"
                        :error="$errors->first('billing_day')"
                        min="1"
                        max="28"
                        required
                        hint="Día del mes para generar facturas (1-28)"
                    />

                    <x-input
                        type="date"
                        name="start_date"
                        label="Fecha de Inicio"
                        :value="old('start_date', date('Y-m-d'))"
                        :error="$errors->first('start_date')"
                        required
                        hint="Fecha de inicio del servicio"
                    />
                </div>

                <div class="mt-4 p-4 bg-info-50 border border-info-200 rounded-lg">
                    <div class="flex gap-3">
                        <x-icon name="information-circle" class="w-5 h-5 text-info-600 flex-shrink-0" />
                        <div class="text-sm text-info-700">
                            <p class="font-medium">Sobre el día de facturación</p>
                            <p class="mt-1">El sistema generará facturas automáticamente el día seleccionado de cada mes según el ciclo configurado.</p>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Addons Opcionales -->
            <x-card title="Servicios Adicionales (Opcional)" class="mb-6">
                <div class="space-y-3">
                    @forelse($addons as $addon)
                        <div class="flex items-center justify-between p-4 border border-secondary-200 rounded-lg hover:bg-secondary-50">
                            <div class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    name="addon_ids[]"
                                    value="{{ $addon->id }}"
                                    id="addon_{{ $addon->id }}"
                                    class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                    {{ in_array($addon->id, old('addon_ids', [])) ? 'checked' : '' }}
                                >
                                <label for="addon_{{ $addon->id }}" class="flex-1 cursor-pointer">
                                    <div class="font-medium text-secondary-900">{{ $addon->name }}</div>
                                    @if($addon->description)
                                        <div class="text-sm text-secondary-500">{{ $addon->description }}</div>
                                    @endif
                                </label>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-secondary-900">
                                    +${{ number_format($addon->price, 2) }}
                                </div>
                                <div class="text-xs text-secondary-500">
                                    @if($addon->billing_type === 'recurring')
                                        /mes
                                    @else
                                        único
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-sm text-secondary-500">
                            No hay servicios adicionales disponibles
                        </div>
                    @endforelse
                </div>
                @error('addon_ids')
                    <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </x-card>

            <!-- Promoción -->
            <x-card title="Promoción (Opcional)" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-select
                        name="promotion_id"
                        label="Aplicar Promoción"
                        placeholder="Sin promoción"
                        :error="$errors->first('promotion_id')"
                    >
                        @foreach($promotions as $promotion)
                            <option value="{{ $promotion->id }}" {{ old('promotion_id') == $promotion->id ? 'selected' : '' }}>
                                {{ $promotion->name }}
                                @if($promotion->discount_type === 'percentage')
                                    - {{ $promotion->discount_value }}% descuento
                                @else
                                    - ${{ number_format($promotion->discount_value, 2) }} descuento
                                @endif
                            </option>
                        @endforeach
                    </x-select>

                    <x-input
                        name="promotion_code"
                        label="Código de Promoción"
                        placeholder="PROMO2024"
                        :value="old('promotion_code')"
                        :error="$errors->first('promotion_code')"
                        hint="Ingrese código promocional si aplica"
                    />
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
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-3 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t border-secondary-200 mt-8">
                <a href="{{ route('subscriptions.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" variant="primary" icon="check">
                    Crear Suscripción
                </x-button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('subscriptionForm', () => ({
                customerId: '{{ old("customer_id") }}',
                planId: '{{ old("plan_id") }}',
                planDetails: {
                    price: '0.00',
                    download: '0',
                    upload: '0'
                },

                init() {
                    if (this.planId) {
                        this.updatePlanDetails();
                    }
                },

                updatePlanDetails() {
                    const select = this.$el.querySelector(`select[name="plan_id"]`);
                    if (select && select.selectedIndex > 0) {
                        const option = select.options[select.selectedIndex];
                        this.planDetails.price = parseFloat(option.dataset.price).toFixed(2);
                        this.planDetails.download = option.dataset.download;
                        this.planDetails.upload = option.dataset.upload;
                    }
                },

                loadCustomerAddresses() {
                    // Esta función podría hacer una llamada AJAX para cargar las direcciones del cliente
                    // Por ahora, solo muestra las direcciones que ya están cargadas
                    console.log('Customer selected:', this.customerId);
                }
            }));
        });
    </script>
    @endpush
@endsection
