@extends('layouts.app')

@section('title', 'Detalle de Suscripción')

@section('breadcrumb')
    <span class="text-secondary-500">Servicios</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('subscriptions.index') }}" class="text-secondary-500 hover:text-secondary-700">Suscripciones</a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">{{ $subscription->code }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Suscripción {{ $subscription->code }}</h1>
                <div class="mt-2">
                    <x-badge
                        :variant="match ($subscription->status->value) {
                            'active' => 'success',
                            'pending_installation' => 'warning',
                            'suspended', 'suspended_voluntary' => 'danger',
                            default => 'secondary',
                        }"
                        dot
                    >
                        {{ $subscription->status->label() }}
                    </x-badge>
                </div>
            </div>

            <div class="flex gap-2">
                @can('subscription.update')
                    <a href="{{ route('subscriptions.edit', $subscription) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <x-card>
                <p class="text-sm font-medium text-secondary-500">Mensualidad</p>
                <p class="mt-2 text-2xl font-bold text-primary-600">S/ {{ number_format($subscription->monthly_price, 2) }}</p>
            </x-card>
            <x-card>
                <p class="text-sm font-medium text-secondary-500">Instalación</p>
                <p class="mt-2 text-2xl font-bold text-secondary-900">S/ {{ number_format($subscription->installation_fee, 2) }}</p>
            </x-card>
            <x-card>
                <p class="text-sm font-medium text-secondary-500">Día facturación</p>
                <p class="mt-2 text-2xl font-bold text-secondary-900">{{ $subscription->billing_day }}</p>
                <p class="text-xs text-secondary-500">{{ $subscription->billing_cycle->value }}</p>
            </x-card>
            <x-card>
                <p class="text-sm font-medium text-secondary-500">Inicio</p>
                <p class="mt-2 text-lg font-bold text-secondary-900">{{ $subscription->start_date?->format('d/m/Y') ?? '-' }}</p>
            </x-card>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-card title="Cliente">
                    <div class="space-y-1">
                        <p class="text-lg font-semibold text-secondary-900">{{ $subscription->customer->getDisplayName() }}</p>
                        <p class="text-sm text-secondary-500">{{ $subscription->customer->document_number }}</p>
                        @if($subscription->customer->email)
                            <p class="text-sm text-secondary-500">{{ $subscription->customer->email }}</p>
                        @endif
                        @if($subscription->customer->phone)
                            <p class="text-sm text-secondary-500">{{ $subscription->customer->phone }}</p>
                        @endif
                    </div>
                </x-card>

                <x-card title="Servicio Contratado">
                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Plan</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->plan->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Velocidad</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->plan->download_speed }}/{{ $subscription->plan->upload_speed }} Mbps</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Permanencia</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->contracted_months ? $subscription->contracted_months . ' meses' : 'Sin permanencia definida' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Promoción</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->promotion?->name ?? 'Sin promoción' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Dirección de Servicio">
                    <p class="text-sm text-secondary-900">{{ $subscription->address?->getFullAddress() ?? 'No especificada' }}</p>
                    @if($subscription->address?->reference)
                        <p class="mt-2 text-sm text-secondary-500">Referencia: {{ $subscription->address->reference }}</p>
                    @endif
                </x-card>

                @if($subscription->serviceInstance)
                    <x-card title="Instancia Técnica">
                        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Estado de provisión</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->serviceInstance->provision_status->value }}</dd>
                            </div>
                            @if($subscription->serviceInstance->pppoe_user)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">Usuario PPPoE</dt>
                                    <dd class="mt-1 font-mono text-sm text-secondary-900">{{ $subscription->serviceInstance->pppoe_user }}</dd>
                                </div>
                            @endif
                            @if($subscription->serviceInstance->ipAddress?->address)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">IP asignada</dt>
                                    <dd class="mt-1 font-mono text-sm text-secondary-900">{{ $subscription->serviceInstance->ipAddress->address }}</dd>
                                </div>
                            @endif
                            @if($subscription->serviceInstance->napPort)
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500">Puerto NAP</dt>
                                    <dd class="mt-1 text-sm text-secondary-900">
                                        {{ $subscription->serviceInstance->napPort->napBox?->name ?? 'NAP' }} / Puerto {{ $subscription->serviceInstance->napPort->port_number }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </x-card>
                @endif

                @if($subscription->addons->count() > 0)
                    <x-card title="Addons">
                        <div class="space-y-3">
                            @foreach($subscription->addons as $addon)
                                <div class="flex items-center justify-between rounded-lg bg-secondary-50 p-3">
                                    <div>
                                        <p class="font-medium text-secondary-900">{{ $addon->name }}</p>
                                        <p class="text-xs text-secondary-500">{{ $addon->is_recurring ? 'Recurrente' : 'Único' }}</p>
                                    </div>
                                    <div class="text-sm font-semibold text-secondary-900">S/ {{ number_format($addon->pivot->price, 2) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                @if($subscription->statusHistory->count() > 0)
                    <x-card title="Historial de Estado">
                        <div class="space-y-3">
                            @foreach($subscription->statusHistory as $history)
                                <div class="rounded-lg border border-secondary-200 p-4">
                                    <p class="text-sm font-medium text-secondary-900">
                                        {{ $history->from_status?->label() ?? 'Sin estado previo' }} → {{ $history->to_status->label() }}
                                    </p>
                                    @if($history->reason)
                                        <p class="mt-1 text-sm text-secondary-500">{{ $history->reason }}</p>
                                    @endif
                                    <p class="mt-2 text-xs text-secondary-500">
                                        {{ $history->user?->name ?? 'Sistema' }} · {{ $history->created_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                @if($subscription->notes->count() > 0)
                    <x-card title="Notas">
                        <div class="space-y-3">
                            @foreach($subscription->notes as $note)
                                <div class="rounded-lg border border-secondary-200 p-4">
                                    <p class="text-sm text-secondary-900">{{ $note->content }}</p>
                                    <p class="mt-2 text-xs text-secondary-500">{{ $note->user?->name ?? 'Sistema' }} · {{ $note->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif
            </div>

            <div class="space-y-6">
                <x-card title="Resumen del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Código</dt>
                            <dd class="mt-1 font-mono text-sm text-secondary-900">{{ $subscription->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Creada</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Último cambio de plan</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->last_plan_change_at?->format('d/m/Y H:i') ?? 'Sin cambios' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Términos aceptados</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $subscription->terms_accepted_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('subscription.update')
                            <a href="{{ route('subscriptions.edit', $subscription) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar</x-button>
                            </a>
                        @endcan

                        @if($subscription->status->value === 'pending_installation')
                            @can('subscription.activate')
                                <form action="{{ route('subscriptions.activate', $subscription) }}" method="POST">
                                    @csrf
                                    <x-button type="submit" variant="success" class="w-full" icon="check">Activar</x-button>
                                </form>
                            @endcan
                        @endif

                        @if($subscription->status->value === 'active')
                            @can('subscription.suspend')
                                <form action="{{ route('subscriptions.suspend', $subscription) }}" method="POST">
                                    @csrf
                                    <x-button type="submit" variant="warning" class="w-full" icon="pause">Suspender</x-button>
                                </form>
                            @endcan
                        @endif

                        @if(in_array($subscription->status->value, ['suspended', 'suspended_voluntary']))
                            @can('subscription.reactivate')
                                <form action="{{ route('subscriptions.reactivate', $subscription) }}" method="POST">
                                    @csrf
                                    <x-button type="submit" variant="success" class="w-full" icon="refresh">Reactivar</x-button>
                                </form>
                            @endcan
                        @endif

                        @if(!in_array($subscription->status->value, ['cancelled', 'terminated']))
                            @can('subscription.cancel')
                                <form action="{{ route('subscriptions.cancel', $subscription) }}" method="POST">
                                    @csrf
                                    <x-button type="submit" variant="danger" class="w-full" icon="x">Cancelar</x-button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
