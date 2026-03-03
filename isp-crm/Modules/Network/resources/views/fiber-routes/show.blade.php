@extends('layouts.app')

@section('title', 'Detalle de Ruta de Fibra')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.fiber-routes.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Rutas de Fibra
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Detalle</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">
                    Ruta: {{ $fiberRoute->fromNode->name }} → {{ $fiberRoute->toNode->name }}
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    @if($fiberRoute->status === 'active')
                        <x-badge variant="success" dot>Activo</x-badge>
                    @elseif($fiberRoute->status === 'inactive')
                        <x-badge variant="danger" dot>Inactivo</x-badge>
                    @else
                        <x-badge variant="warning" dot>Mantenimiento</x-badge>
                    @endif
                    @if($fiberRoute->distance_meters)
                        <span class="text-sm text-secondary-500">{{ $fiberRoute->distance_km }} km</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                @can('network.fiberroute.update')
                    <a href="{{ route('network.fiber-routes.edit', $fiberRoute) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Visualización de la Ruta -->
        <x-card>
            <div class="flex items-center justify-between py-8">
                <!-- Nodo Origen -->
                <div class="flex-1 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary-100 mb-3">
                        <x-icon name="network" class="w-10 h-10 text-primary-600" />
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900">{{ $fiberRoute->fromNode->name }}</h3>
                    <p class="text-sm text-secondary-500">{{ $fiberRoute->fromNode->code }}</p>
                    <a href="{{ route('network.nodes.show', $fiberRoute->fromNode) }}" class="text-xs text-primary-600 hover:text-primary-900 mt-1 inline-block">
                        Ver detalle
                    </a>
                </div>

                <!-- Conexión -->
                <div class="flex-1 flex flex-col items-center px-4">
                    <div class="w-full border-t-4 border-dashed border-primary-300 relative">
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white px-4">
                            <x-icon name="arrow-right" class="w-8 h-8 text-primary-600" />
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        @if($fiberRoute->distance_meters)
                            <p class="text-2xl font-bold text-primary-600">{{ $fiberRoute->distance_km }} km</p>
                            <p class="text-xs text-secondary-500">{{ number_format($fiberRoute->distance_meters) }} metros</p>
                        @endif
                        @if($fiberRoute->fiber_count)
                            <div class="mt-2">
                                <x-badge variant="info">{{ $fiberRoute->fiber_count }} hilos</x-badge>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Nodo Destino -->
                <div class="flex-1 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-success-100 mb-3">
                        <x-icon name="network" class="w-10 h-10 text-success-600" />
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900">{{ $fiberRoute->toNode->name }}</h3>
                    <p class="text-sm text-secondary-500">{{ $fiberRoute->toNode->code }}</p>
                    <a href="{{ route('network.nodes.show', $fiberRoute->toNode) }}" class="text-xs text-primary-600 hover:text-primary-900 mt-1 inline-block">
                        Ver detalle
                    </a>
                </div>
            </div>
        </x-card>

        <!-- Layout 2 columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal -->
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Información de la Ruta">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nodo de Origen</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                <a href="{{ route('network.nodes.show', $fiberRoute->fromNode) }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $fiberRoute->fromNode->name }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nodo de Destino</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                <a href="{{ route('network.nodes.show', $fiberRoute->toNode) }}" class="text-primary-600 hover:text-primary-900">
                                    {{ $fiberRoute->toNode->name }}
                                </a>
                            </dd>
                        </div>
                        @if($fiberRoute->distance_meters)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Distancia</dt>
                                <dd class="mt-1 text-sm text-secondary-900">
                                    <span class="font-semibold">{{ $fiberRoute->distance_km }} km</span>
                                    <span class="text-xs text-secondary-500">({{ number_format($fiberRoute->distance_meters) }} m)</span>
                                </dd>
                            </div>
                        @endif
                        @if($fiberRoute->fiber_count)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Número de Hilos</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $fiberRoute->fiber_count }} fibras</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Estado</dt>
                            <dd class="mt-1">
                                @if($fiberRoute->status === 'active')
                                    <x-badge variant="success">Activo</x-badge>
                                @elseif($fiberRoute->status === 'inactive')
                                    <x-badge variant="danger">Inactivo</x-badge>
                                @else
                                    <x-badge variant="warning">Mantenimiento</x-badge>
                                @endif
                            </dd>
                        </div>
                        @if($fiberRoute->notes)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-secondary-500">Notas</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $fiberRoute->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                <x-card title="Ubicaciones de los Nodos">
                    <div class="space-y-4">
                        <div class="p-4 bg-primary-50 rounded-lg">
                            <h4 class="font-medium text-primary-900">{{ $fiberRoute->fromNode->name }}</h4>
                            <p class="text-sm text-primary-700 mt-1">{{ $fiberRoute->fromNode->address }}</p>
                            @if($fiberRoute->fromNode->latitude && $fiberRoute->fromNode->longitude)
                                <p class="text-xs text-primary-600 mt-2 font-mono">
                                    {{ $fiberRoute->fromNode->latitude }}, {{ $fiberRoute->fromNode->longitude }}
                                </p>
                            @endif
                        </div>

                        <div class="flex justify-center">
                            <x-icon name="arrow-down" class="w-6 h-6 text-secondary-400" />
                        </div>

                        <div class="p-4 bg-success-50 rounded-lg">
                            <h4 class="font-medium text-success-900">{{ $fiberRoute->toNode->name }}</h4>
                            <p class="text-sm text-success-700 mt-1">{{ $fiberRoute->toNode->address }}</p>
                            @if($fiberRoute->toNode->latitude && $fiberRoute->toNode->longitude)
                                <p class="text-xs text-success-600 mt-2 font-mono">
                                    {{ $fiberRoute->toNode->latitude }}, {{ $fiberRoute->toNode->longitude }}
                                </p>
                            @endif
                        </div>
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
                                {{ $fiberRoute->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $fiberRoute->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('network.fiberroute.update')
                            <a href="{{ route('network.fiber-routes.edit', $fiberRoute) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar</x-button>
                            </a>
                        @endcan
                        @can('network.fiberroute.delete')
                            <form action="{{ route('network.fiber-routes.destroy', $fiberRoute) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar esta ruta de fibra?')">
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
