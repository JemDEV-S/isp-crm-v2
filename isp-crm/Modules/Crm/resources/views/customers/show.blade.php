@extends('layouts.app')

@section('title', 'Detalle del Cliente')

@section('breadcrumb')
    <span class="text-secondary-500">CRM</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('crm.customers.index') }}" class="text-secondary-500 hover:text-secondary-700">Clientes</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $customer->getDisplayName() }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $customer->getDisplayName() }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <x-badge :variant="$customer->is_active ? 'success' : 'danger'" dot>{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</x-badge>
                    <x-badge :variant="$customer->customer_type->value === 'business' ? 'info' : 'default'" size="sm">{{ $customer->customer_type->label() }}</x-badge>
                    <span class="text-xs text-secondary-500">{{ $customer->code }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                @can('crm.customer.update')
                    <a href="{{ route('crm.customers.edit', $customer) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-card title="Ficha Comercial">
                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nombre / razón social</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->name }}</dd>
                        </div>
                        @if($customer->trade_name)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Nombre comercial</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $customer->trade_name }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Documento</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->document_type->label() }} · {{ $customer->document_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Límite de crédito</dt>
                            <dd class="mt-1 text-sm text-secondary-900">S/ {{ number_format($customer->credit_limit ?? 0, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Correo principal</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->email ?? 'No registrado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Correo de facturación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->billing_email ?? 'No registrado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Teléfono</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->phone }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Exento de impuestos</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->tax_exempt ? 'Sí' : 'No' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Direcciones">
                    @if($customer->addresses->count() > 0)
                        <div class="space-y-3">
                            @foreach($customer->addresses as $address)
                                <div class="rounded-lg bg-secondary-50 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-medium text-secondary-900">{{ $address->label ?: $address->type->label() }}</p>
                                        <x-badge :variant="$address->type->value === 'service' ? 'primary' : 'info'" size="sm">{{ $address->type->label() }}</x-badge>
                                        @if($address->is_default)
                                            <x-badge variant="success" size="sm">Por defecto</x-badge>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm text-secondary-700">{{ $address->getFullAddress() }}</p>
                                    @if($address->reference)
                                        <p class="mt-1 text-xs text-secondary-500">Referencia: {{ $address->reference }}</p>
                                    @endif
                                    @if($address->zone)
                                        <p class="mt-1 text-xs text-secondary-500">Zona: {{ $address->zone->name }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-secondary-500">Todavía no hay direcciones registradas para este cliente.</p>
                    @endif
                </x-card>

                <x-card title="Contactos">
                    @if($customer->contacts->count() > 0)
                        <div class="space-y-3">
                            @foreach($customer->contacts as $contact)
                                <div class="flex items-center justify-between rounded-lg bg-secondary-50 p-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium text-secondary-900">{{ $contact->name }}</p>
                                            @if($contact->is_primary)
                                                <x-badge variant="primary" size="sm">Principal</x-badge>
                                            @endif
                                        </div>
                                        <p class="text-sm text-secondary-500">{{ $contact->type->label() }} · {{ $contact->value }}</p>
                                        @if($contact->relationship)
                                            <p class="text-xs text-secondary-500">{{ $contact->relationship }}</p>
                                        @endif
                                    </div>
                                    @if($contact->receives_notifications)
                                        <x-badge variant="success" size="sm">Recibe notificaciones</x-badge>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-secondary-500">No hay contactos adicionales registrados.</p>
                    @endif
                </x-card>

                <x-card title="Notas">
                    @if($customer->notes->count() > 0)
                        <div class="space-y-3">
                            @foreach($customer->notes as $note)
                                <div class="rounded-lg border border-secondary-200 p-4">
                                    <div class="flex items-center gap-2">
                                        @if($note->is_pinned)
                                            <x-badge variant="warning" size="sm">Destacada</x-badge>
                                        @endif
                                        <span class="text-xs text-secondary-500">{{ $note->user?->name ?? 'Sistema' }} · {{ $note->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="mt-2 text-sm text-secondary-900">{{ $note->content }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-secondary-500">Aún no hay notas registradas.</p>
                    @endif
                </x-card>

                <x-card title="Documentos">
                    @if($customer->documents->count() > 0)
                        <div class="space-y-3">
                            @foreach($customer->documents as $document)
                                <div class="rounded-lg border border-secondary-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-medium text-secondary-900">{{ $document->type }}</p>
                                            <p class="text-xs text-secondary-500">{{ $document->file_name ?: $document->file_path }}</p>
                                        </div>
                                        <x-badge :variant="$document->isVerified() ? 'success' : 'warning'" size="sm">
                                            {{ $document->isVerified() ? 'Verificado' : 'Pendiente' }}
                                        </x-badge>
                                    </div>
                                    @if($document->expires_at)
                                        <p class="mt-2 text-xs text-secondary-500">Vence: {{ $document->expires_at->format('d/m/Y') }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-secondary-500">No hay documentos cargados.</p>
                    @endif
                </x-card>
            </div>

            <div class="space-y-6">
                @if($customer->lead)
                    <x-card title="Origen del Cliente">
                        <p class="text-sm text-secondary-600">Este cliente fue convertido desde un lead comercial.</p>
                        <div class="mt-4">
                            <a href="{{ route('crm.leads.show', $customer->lead) }}">
                                <x-button variant="outline" class="w-full" icon="arrow-right">Ver lead origen</x-button>
                            </a>
                        </div>
                    </x-card>
                @endif

                <x-card title="Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">UUID</dt>
                            <dd class="mt-1 break-all font-mono text-xs text-secondary-900">{{ $customer->uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Código</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Creado</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Actualizado</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $customer->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Pendientes Sugeridos">
                    <ul class="space-y-3 text-sm text-secondary-600">
                        <li>Verificar si ya tiene dirección de facturación y servicio marcadas por defecto.</li>
                        <li>Confirmar que exista al menos un contacto que reciba notificaciones.</li>
                        <li>Cargar documentos y notas de validación comercial cuando aplique.</li>
                    </ul>
                </x-card>
            </div>
        </div>
    </div>
@endsection
