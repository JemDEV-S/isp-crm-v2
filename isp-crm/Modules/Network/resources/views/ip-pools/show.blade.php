@extends('layouts.app')

@section('title', 'Detalle de Pool de IP')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.ip-pools.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Pools de IP
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">{{ $pool->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $pool->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-sm text-secondary-500 font-mono">{{ $pool->network_cidr }}</span>
                    <x-badge :variant="$pool->is_active ? 'success' : 'danger'" dot>
                        {{ $pool->is_active ? 'Activo' : 'Inactivo' }}
                    </x-badge>
                    <x-badge :variant="$pool->type === 'public' ? 'info' : 'secondary'" size="sm">
                        {{ strtoupper($pool->type) }}
                    </x-badge>
                </div>
            </div>
            <div class="flex gap-2">
                @can('network.ippool.update')
                    <a href="{{ route('network.ip-pools.edit', $pool) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Total IPs</p>
                        <p class="mt-1 text-2xl font-bold text-secondary-900">{{ $stats['total'] ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                        <x-icon name="network" class="w-6 h-6 text-primary-600" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">IPs Libres</p>
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
                        <p class="text-sm font-medium text-secondary-500">IPs Asignadas</p>
                        <p class="mt-1 text-2xl font-bold text-warning-600">{{ $stats['assigned'] ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-warning-100 flex items-center justify-center">
                        <x-icon name="users" class="w-6 h-6 text-warning-600" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-secondary-500">Uso</p>
                        <p class="mt-1 text-2xl font-bold text-secondary-900">{{ number_format($stats['usage_percentage'] ?? 0, 1) }}%</p>
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
                <x-card title="Información del Pool">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nombre</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $pool->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Red CIDR</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $pool->network_cidr }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Gateway</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $pool->gateway }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipo</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ strtoupper($pool->type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">DNS Primario</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $pool->dns_primary ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">DNS Secundario</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $pool->dns_secondary ?? '-' }}</dd>
                        </div>
                        @if($pool->vlan_id)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">VLAN ID</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $pool->vlan_id }}</dd>
                            </div>
                        @endif
                        @if($pool->device)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Dispositivo</dt>
                                <dd class="mt-1 text-sm text-secondary-900">
                                    <a href="{{ route('network.devices.show', $pool->device) }}" class="text-primary-600 hover:text-primary-900">
                                        {{ $pool->device->brand }} {{ $pool->device->model }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                        @if($pool->description)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-secondary-500">Descripción</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $pool->description }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                <x-card>
                    <x-slot name="header">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-secondary-900">Direcciones IP</h3>
                            <a href="{{ route('network.ip-pools.addresses', $pool) }}">
                                <x-button variant="ghost" size="sm">Ver todas</x-button>
                            </a>
                        </div>
                    </x-slot>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-secondary-900">Capacidad total</p>
                                <p class="text-xs text-secondary-500">Direcciones disponibles en el pool</p>
                            </div>
                            <p class="text-lg font-bold text-secondary-900">{{ $stats['total'] ?? 0 }}</p>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-success-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-success-900">Libres</p>
                                <p class="text-xs text-success-600">Disponibles para asignar</p>
                            </div>
                            <p class="text-lg font-bold text-success-700">{{ $stats['free'] ?? 0 }}</p>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-warning-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-warning-900">Asignadas</p>
                                <p class="text-xs text-warning-600">En uso por clientes</p>
                            </div>
                            <p class="text-lg font-bold text-warning-700">{{ $stats['assigned'] ?? 0 }}</p>
                        </div>

                        @if(($stats['reserved'] ?? 0) > 0)
                            <div class="flex items-center justify-between p-3 bg-info-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-info-900">Reservadas</p>
                                    <p class="text-xs text-info-600">IPs reservadas</p>
                                </div>
                                <p class="text-lg font-bold text-info-700">{{ $stats['reserved'] }}</p>
                            </div>
                        @endif
                    </div>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $pool->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $pool->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('network.ippool.update')
                            <a href="{{ route('network.ip-pools.edit', $pool) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar</x-button>
                            </a>
                        @endcan
                        <a href="{{ route('network.ip-pools.addresses', $pool) }}" class="block">
                            <x-button variant="outline" class="w-full" icon="network">Ver Direcciones IP</x-button>
                        </a>
                        @can('network.ippool.delete')
                            <form action="{{ route('network.ip-pools.destroy', $pool) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar este pool?')">
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
