@extends('layouts.app')

@section('title', 'Detalle de Caja NAP')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.nap-boxes.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Cajas NAP
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">{{ $napBox->code }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $napBox->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-sm text-secondary-500">{{ $napBox->code }}</span>
                    @if($napBox->status === 'active')
                        <x-badge variant="success" dot>Activo</x-badge>
                    @elseif($napBox->status === 'inactive')
                        <x-badge variant="danger" dot>Inactivo</x-badge>
                    @else
                        <x-badge variant="warning" dot>Mantenimiento</x-badge>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                @can('network.napbox.update')
                    <a href="{{ route('network.nap-boxes.edit', $napBox) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Estadísticas de Puertos -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Total Puertos</p>
                        <p class="mt-1 text-2xl font-bold text-secondary-900">{{ $napBox->total_ports }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                        <x-icon name="network" class="w-6 h-6 text-primary-600" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Puertos Libres</p>
                        <p class="mt-1 text-2xl font-bold text-success-600">{{ $stats['free'] ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-success-100 flex items-center justify-center">
                        <x-icon name="check-circle" class="w-6 h-6 text-success-600" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Puertos Ocupados</p>
                        <p class="mt-1 text-2xl font-bold text-warning-600">{{ $stats['occupied'] ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-warning-100 flex items-center justify-center">
                        <x-icon name="users" class="w-6 h-6 text-warning-600" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Ocupación</p>
                        <p class="mt-1 text-2xl font-bold text-secondary-900">{{ number_format($stats['occupancy_percentage'] ?? 0, 1) }}%</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-secondary-100 flex items-center justify-center">
                        <x-icon name="chart" class="w-6 h-6 text-secondary-600" />
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Layout 2 columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal -->
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Información General">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Código</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $napBox->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nombre</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $napBox->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipo</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $napBox->type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nodo</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                <a href="{{ route('network.nodes.show', $napBox->node) }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $napBox->node->name }}
                                </a>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Dirección</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $napBox->address ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Latitud</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $napBox->latitude }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Longitud</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $napBox->longitude }}</dd>
                        </div>
                        @if($napBox->notes)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-secondary-500">Notas</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $napBox->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                <!-- Puertos -->
                <x-card title="Puertos">
                    <x-slot name="header">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-secondary-900">Puertos</h3>
                            <a href="{{ route('network.nap-boxes.ports', $napBox) }}">
                                <x-button variant="ghost" size="sm">Ver todos</x-button>
                            </a>
                        </div>
                    </x-slot>

                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                        @foreach($napBox->ports->take(32) as $port)
                            <div
                                class="aspect-square rounded-lg border-2 flex items-center justify-center text-xs font-semibold
                                @if($port->status === 'free') border-success-300 bg-success-50 text-success-700
                                @elseif($port->status === 'occupied') border-warning-300 bg-warning-50 text-warning-700
                                @elseif($port->status === 'reserved') border-info-300 bg-info-50 text-info-700
                                @else border-danger-300 bg-danger-50 text-danger-700
                                @endif"
                                title="Puerto {{ $port->port_number }} - {{ ucfirst($port->status) }}"
                            >
                                {{ $port->port_number }}
                            </div>
                        @endforeach
                    </div>

                    @if($napBox->ports->count() > 32)
                        <div class="mt-4 text-center">
                            <a href="{{ route('network.nap-boxes.ports', $napBox) }}">
                                <x-button variant="ghost" size="sm">Ver todos los {{ $napBox->ports->count() }} puertos</x-button>
                            </a>
                        </div>
                    @endif
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de instalación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $napBox->installed_at?->format('d/m/Y') ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $napBox->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $napBox->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('network.napbox.update')
                            <a href="{{ route('network.nap-boxes.edit', $napBox) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar</x-button>
                            </a>
                        @endcan
                        <a href="{{ route('network.nap-boxes.ports', $napBox) }}" class="block">
                            <x-button variant="outline" class="w-full" icon="network">Gestionar Puertos</x-button>
                        </a>
                        @can('network.napbox.delete')
                            <form action="{{ route('network.nap-boxes.destroy', $napBox) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar esta caja NAP?')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                    Eliminar
                                </x-button>
                            </form>
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
