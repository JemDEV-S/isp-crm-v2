@extends('layouts.app')

@section('title', 'Detalle de Rol')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('accesscontrol.roles.index') }}" class="text-secondary-500 hover:text-secondary-700">Roles</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $role->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 rounded-lg bg-primary-100 flex items-center justify-center">
                    <x-icon name="shield-check" class="w-8 h-8 text-primary-600" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900">{{ $role->name }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-secondary-500 font-mono">{{ $role->code }}</span>
                        @if($role->is_system)
                            <x-badge variant="info">Sistema</x-badge>
                        @endif
                        <x-badge :variant="$role->is_active ? 'success' : 'danger'" dot>
                            {{ $role->is_active ? 'Activo' : 'Inactivo' }}
                        </x-badge>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                @if(!$role->is_system)
                    @can('update', $role)
                        <a href="{{ route('accesscontrol.roles.edit', $role) }}">
                            <x-button variant="secondary" icon="pencil">Editar</x-button>
                        </a>
                    @endcan
                @endif
                <a href="{{ route('accesscontrol.roles.permissions', $role) }}">
                    <x-button icon="key">Gestionar Permisos</x-button>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Descripción">
                    <p class="text-secondary-700">
                        {{ $role->description ?? 'Sin descripción' }}
                    </p>
                </x-card>

                <x-card title="Permisos Asignados">
                    @php
                        $permissionsByModule = $role->permissions->groupBy('module');
                    @endphp

                    @if($role->permissions->count() > 0)
                        <div class="space-y-4">
                            @foreach($permissionsByModule as $module => $modulePermissions)
                                <div class="border border-secondary-200 rounded-lg overflow-hidden">
                                    <div class="px-4 py-3 bg-secondary-50 flex items-center justify-between">
                                        <span class="font-medium text-secondary-900 capitalize">{{ $module }}</span>
                                        <x-badge variant="default" size="sm">{{ $modulePermissions->count() }}</x-badge>
                                    </div>
                                    <div class="p-4">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($modulePermissions as $permission)
                                                <x-badge variant="primary" size="sm">{{ $permission->name }}</x-badge>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-secondary-500">Este rol no tiene permisos asignados</p>
                    @endif
                </x-card>

                <x-card title="Usuarios con este Rol">
                    @if($role->users->count() > 0)
                        <div class="space-y-3">
                            @foreach($role->users->take(10) as $user)
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

                            @if($role->users->count() > 10)
                                <p class="text-sm text-secondary-500 text-center">
                                    Y {{ $role->users->count() - 10 }} usuarios más...
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-secondary-500">No hay usuarios con este rol</p>
                    @endif
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <x-card title="Estadísticas">
                    <dl class="space-y-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-secondary-500">Usuarios asignados</dt>
                            <dd class="text-lg font-semibold text-secondary-900">{{ $role->users_count }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-secondary-500">Permisos totales</dt>
                            <dd class="text-lg font-semibold text-secondary-900">{{ $role->permissions_count }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Información">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $role->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $role->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipo de rol</dt>
                            <dd class="mt-1">
                                <x-badge :variant="$role->is_system ? 'info' : 'default'">
                                    {{ $role->is_system ? 'Rol del Sistema' : 'Rol Personalizado' }}
                                </x-badge>
                            </dd>
                        </div>
                    </dl>
                </x-card>

                @if(!$role->is_system)
                    <x-card title="Acciones">
                        <div class="space-y-2">
                            @can('update', $role)
                                <a href="{{ route('accesscontrol.roles.edit', $role) }}" class="block">
                                    <x-button variant="outline" class="w-full" icon="pencil">Editar Rol</x-button>
                                </a>
                            @endcan
                            @can('delete', $role)
                                @if($role->users->count() == 0)
                                    <form action="{{ route('accesscontrol.roles.destroy', $role) }}" method="POST"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este rol?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                            Eliminar Rol
                                        </x-button>
                                    </form>
                                @else
                                    <p class="text-xs text-secondary-500 text-center">
                                        No se puede eliminar un rol con usuarios asignados
                                    </p>
                                @endif
                            @endcan
                        </div>
                    </x-card>
                @endif
            </div>
        </div>
    </div>
@endsection
