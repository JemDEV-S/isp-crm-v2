@extends('layouts.app')

@section('title', 'Nueva Suscripción')

@section('breadcrumb')
    <span class="text-secondary-500">Servicios</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('subscriptions.index') }}" class="text-secondary-500 hover:text-secondary-700">Suscripciones</a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Nueva Suscripción</span>
@endsection

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Nueva Suscripción</h1>
            <p class="mt-1 text-sm text-secondary-500">Relaciona cliente, dirección y condiciones comerciales del servicio.</p>
        </div>

        <form action="{{ route('subscriptions.store') }}" method="POST" x-data="subscriptionForm()">
            @csrf

            <x-card title="Cliente y Plan" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-select
                        name="customer_id"
                        label="Cliente"
                        :error="$errors->first('customer_id')"
                        required
                        x-model="customerId"
                        @change="loadCustomerAddresses()"
                    >
                        <option value="">Seleccione un cliente...</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                {{ $customer->getDisplayName() }} - {{ $customer->document_number }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select
                        name="plan_id"
                        label="Plan"
                        :error="$errors->first('plan_id')"
                        required
                        x-model="planId"
                        @change="updatePlanDetails()"
                    >
                        <option value="">Seleccione un plan...</option>
                        @foreach($plans as $plan)
                            <option
                                value="{{ $plan->id }}"
                                data-price="{{ $plan->price }}"
                                data-installation-fee="{{ $plan->installation_fee }}"
                                data-download="{{ $plan->download_speed }}"
                                data-upload="{{ $plan->upload_speed }}"
                                @selected((string) old('plan_id') === (string) $plan->id)
                            >
                                {{ $plan->name }} - S/ {{ number_format($plan->price, 2) }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div x-show="planId" class="mt-4 rounded-lg border border-primary-200 bg-primary-50 p-4">
                    <p class="text-sm font-medium text-primary-800">Resumen comercial</p>
                    <p class="mt-1 text-sm text-primary-700">
                        Velocidad: <span x-text="planDetails.download"></span>/<span x-text="planDetails.upload"></span> Mbps
                    </p>
                    <p class="text-sm text-primary-700">
                        Mensualidad: S/ <span x-text="planDetails.price"></span> |
                        Instalación: S/ <span x-text="planDetails.installationFee"></span>
                    </p>
                </div>
            </x-card>

            <x-card title="Dirección del Servicio" class="mb-6">
                <div>
                    <label for="service_address_id" class="mb-1 block text-sm font-medium text-secondary-700">Dirección</label>
                    <select
                        id="service_address_id"
                        name="service_address_id"
                        x-model="serviceAddressId"
                        class="block w-full rounded-lg border-secondary-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        required
                    >
                        <option value="">Seleccione una dirección...</option>
                        <template x-for="address in availableAddresses" :key="address.id">
                            <option :value="address.id" x-text="address.label"></option>
                        </template>
                    </select>
                    @error('service_address_id')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-secondary-500">Solo se muestran direcciones de tipo servicio del cliente seleccionado.</p>
                </div>
            </x-card>

            <x-card title="Facturación y Permanencia" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                    <x-select name="billing_cycle" label="Ciclo" :error="$errors->first('billing_cycle')" required>
                        <option value="monthly" @selected(old('billing_cycle', 'monthly') === 'monthly')>Mensual</option>
                        <option value="quarterly" @selected(old('billing_cycle') === 'quarterly')>Trimestral</option>
                        <option value="semiannual" @selected(old('billing_cycle') === 'semiannual')>Semestral</option>
                        <option value="annual" @selected(old('billing_cycle') === 'annual')>Anual</option>
                    </x-select>

                    <x-input
                        type="number"
                        name="billing_day"
                        label="Día facturación"
                        :value="old('billing_day', 1)"
                        :error="$errors->first('billing_day')"
                        min="1"
                        max="28"
                        required
                    />

                    <x-input
                        type="date"
                        name="start_date"
                        label="Inicio"
                        :value="old('start_date', date('Y-m-d'))"
                        :error="$errors->first('start_date')"
                        required
                    />

                    <x-input
                        type="number"
                        name="contracted_months"
                        label="Permanencia"
                        :value="old('contracted_months')"
                        :error="$errors->first('contracted_months')"
                        min="1"
                        max="60"
                        hint="Opcional, en meses"
                    />
                </div>
            </x-card>

            <x-card title="Servicios Adicionales" class="mb-6">
                <div class="space-y-3">
                    @forelse($addons as $addon)
                        <label class="flex items-center justify-between rounded-lg border border-secondary-200 p-4 hover:bg-secondary-50">
                            <div class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    name="addon_ids[]"
                                    value="{{ $addon->id }}"
                                    class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    @checked(in_array($addon->id, old('addon_ids', [])))
                                >
                                <div>
                                    <div class="font-medium text-secondary-900">{{ $addon->name }}</div>
                                    @if($addon->description)
                                        <div class="text-sm text-secondary-500">{{ $addon->description }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right text-sm font-semibold text-secondary-900">
                                S/ {{ number_format($addon->price, 2) }}
                                <div class="text-xs font-normal text-secondary-500">{{ $addon->is_recurring ? 'Recurrente' : 'Único' }}</div>
                            </div>
                        </label>
                    @empty
                        <p class="text-sm text-secondary-500">No hay addons activos disponibles.</p>
                    @endforelse
                </div>
            </x-card>

            <x-card title="Promoción y Notas" class="mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-select name="promotion_id" label="Promoción" :error="$errors->first('promotion_id')">
                        <option value="">Sin promoción</option>
                        @foreach($promotions as $promotion)
                            <option value="{{ $promotion->id }}" @selected((string) old('promotion_id') === (string) $promotion->id)>
                                {{ $promotion->name }} - {{ $promotion->formatted_discount }}
                            </option>
                        @endforeach
                    </x-select>

                    <div>
                        <label for="notes" class="mb-1 block text-sm font-medium text-secondary-700">Notas internas</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="block w-full rounded-lg border-secondary-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Observaciones comerciales, coordinación o acuerdos..."
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <div class="sticky bottom-0 mt-8 flex items-center justify-end gap-3 border-t border-secondary-200 bg-white/80 py-4 backdrop-blur">
                <a href="{{ route('subscriptions.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Crear Suscripción</x-button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('subscriptionForm', () => ({
                    addressesByCustomer: @js($addressesByCustomer),
                    customerId: '{{ old('customer_id') }}',
                    planId: '{{ old('plan_id') }}',
                    serviceAddressId: '{{ old('service_address_id') }}',
                    planDetails: {
                        price: '0.00',
                        installationFee: '0.00',
                        download: '0',
                        upload: '0'
                    },

                    get availableAddresses() {
                        return this.addressesByCustomer[this.customerId] ?? [];
                    },

                    init() {
                        this.updatePlanDetails();
                        this.ensureSelectedAddress();
                    },

                    updatePlanDetails() {
                        const select = this.$el.querySelector('select[name="plan_id"]');
                        const option = select?.options[select.selectedIndex];

                        if (!option?.dataset?.price) {
                            this.planDetails = { price: '0.00', installationFee: '0.00', download: '0', upload: '0' };
                            return;
                        }

                        this.planDetails = {
                            price: Number(option.dataset.price).toFixed(2),
                            installationFee: Number(option.dataset.installationFee || 0).toFixed(2),
                            download: option.dataset.download,
                            upload: option.dataset.upload,
                        };
                    },

                    loadCustomerAddresses() {
                        this.ensureSelectedAddress();
                    },

                    ensureSelectedAddress() {
                        const exists = this.availableAddresses.some(address => String(address.id) === String(this.serviceAddressId));
                        if (exists) {
                            return;
                        }

                        const fallback = this.availableAddresses.find(address => address.is_default) ?? this.availableAddresses[0];
                        this.serviceAddressId = fallback ? String(fallback.id) : '';
                    }
                }));
            });
        </script>
    @endpush
@endsection
