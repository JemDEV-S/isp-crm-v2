@extends('layouts.app')

@section('title', 'Detalle de Zona')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('accesscontrol.zones.index') }}" class="text-secondary-500 hover:text-secondary-700">Zonas</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $zone->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 rounded-lg bg-success-100 flex items-center justify-center">
                    <x-icon name="map-pin" class="w-8 h-8 text-success-600" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900">{{ $zone->name }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-secondary-500 font-mono">{{ $zone->code }}</span>
                        <x-badge :variant="$zone->is_active ? 'success' : 'danger'" dot>
                            {{ $zone->is_active ? 'Activa' : 'Inactiva' }}
                        </x-badge>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                @can('update', $zone)
                    <a href="{{ route('accesscontrol.zones.edit', $zone) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
                <form action="{{ route('accesscontrol.zones.toggle-status', $zone) }}" method="POST" class="inline">
                    @csrf
                    <x-button type="submit" :variant="$zone->is_active ? 'warning' : 'success'">
                        {{ $zone->is_active ? 'Desactivar' : 'Activar' }}
                    </x-button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Información General">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Código</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $zone->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Zona Padre</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                @if($zone->parent)
                                    <a href="{{ route('accesscontrol.zones.show', $zone->parent) }}" class="text-primary-600 hover:text-primary-700">
                                        {{ $zone->parent->name }}
                                    </a>
                                @else
                                    <span class="text-secondary-500">-</span>
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Descripción</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $zone->description ?? 'Sin descripción' }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                @if($zone->children->count() > 0)
                    <x-card title="Sub-zonas">
                        <div class="space-y-3">
                            @foreach($zone->children as $child)
                                <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-lg bg-success-100 flex items-center justify-center">
                                            <x-icon name="map-pin" class="w-5 h-5 text-success-600" />
                                        </div>
                                        <div>
                                            <span class="font-medium text-secondary-900">{{ $child->name }}</span>
                                            <p class="text-xs text-secondary-500 font-mono">{{ $child->code }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('accesscontrol.zones.show', $child) }}" class="text-primary-600 hover:text-primary-700">
                                        <x-icon name="arrow-right" class="w-5 h-5" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                <x-card title="Usuarios en esta Zona">
                    @if($zone->users->count() > 0)
                        <div class="space-y-3">
                            @foreach($zone->users as $user)
                                <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                            <span class="text-sm font-semibold text-primary-700">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-secondary-900">{{ $user->name }}</span>
                                            <p class="text-sm text-secondary-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('accesscontrol.users.show', $user) }}" class="text-primary-600 hover:text-primary-700">
                                        <x-icon name="arrow-right" class="w-5 h-5" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-secondary-500">No hay usuarios asignados a esta zona</p>
                    @endif
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <x-card title="Estadísticas">
                    <dl class="space-y-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-secondary-500">Sub-zonas</dt>
                            <dd class="text-lg font-semibold text-secondary-900">{{ $zone->children_count }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-secondary-500">Usuarios asignados</dt>
                            <dd class="text-lg font-semibold text-secondary-900">{{ $zone->users_count }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Información">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $zone->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $zone->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Área geográfica</dt>
                            <dd class="mt-1">
                                <x-badge :variant="$zone->polygon ? 'success' : 'default'">
                                    {{ $zone->polygon ? 'Definida' : 'No definida' }}
                                </x-badge>
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('update', $zone)
                            <a href="{{ route('accesscontrol.zones.edit', $zone) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar Zona</x-button>
                            </a>
                        @endcan
                        @can('delete', $zone)
                            @if($zone->children_count == 0 && $zone->users_count == 0)
                                <form action="{{ route('accesscontrol.zones.destroy', $zone) }}" method="POST"
                                      onsubmit="return confirm('¿Estás seguro de eliminar esta zona?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                        Eliminar Zona
                                    </x-button>
                                </form>
                            @else
                                <p class="text-xs text-secondary-500 text-center">
                                    No se puede eliminar una zona con sub-zonas o usuarios asignados
                                </p>
                            @endif
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
