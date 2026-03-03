@extends('layouts.app')

@section('title', 'Roles')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Roles</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Roles</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona los roles y permisos del sistema</p>
            </div>
            @can('create', \Modules\AccessControl\Entities\Role::class)
                <a href="{{ route('accesscontrol.roles.create') }}">
                    <x-button icon="plus">
                        Nuevo Rol
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('accesscontrol.roles.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre o código..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />
                </div>

                <div class="w-40">
                    <x-select name="is_active" label="Estado" placeholder="Todos">
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </x-select>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('accesscontrol.roles.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Roles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($roles as $role)
                <x-card class="hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                                <x-icon name="shield-check" class="w-6 h-6 text-primary-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900">{{ $role->name }}</h3>
                                <p class="text-sm text-secondary-500 font-mono">{{ $role->code }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            @if($role->is_system)
                                <x-badge variant="info" size="sm">Sistema</x-badge>
                            @endif
                            <x-badge :variant="$role->is_active ? 'success' : 'danger'" size="sm" dot>
                                {{ $role->is_active ? 'Activo' : 'Inactivo' }}
                            </x-badge>
                        </div>
                    </div>

                    @if($role->description)
                        <p class="mt-3 text-sm text-secondary-600">{{ $role->description }}</p>
                    @endif

                    <div class="mt-4 flex items-center gap-4 text-sm text-secondary-500">
                        <span class="flex items-center gap-1">
                            <x-icon name="users" class="w-4 h-4" />
                            {{ $role->users_count }} usuarios
                        </span>
                        <span class="flex items-center gap-1">
                            <x-icon name="key" class="w-4 h-4" />
                            {{ $role->permissions_count }} permisos
                        </span>
                    </div>

                    <div class="mt-4 pt-4 border-t border-secondary-200 flex justify-end gap-2">
                        <a href="{{ route('accesscontrol.roles.show', $role) }}">
                            <x-button variant="ghost" size="sm" icon="eye">Ver</x-button>
                        </a>
                        @if(!$role->is_system)
                            @can('update', $role)
                                <a href="{{ route('accesscontrol.roles.edit', $role) }}">
                                    <x-button variant="ghost" size="sm" icon="pencil">Editar</x-button>
                                </a>
                            @endcan
                        @endif
                        <a href="{{ route('accesscontrol.roles.permissions', $role) }}">
                            <x-button variant="secondary" size="sm" icon="key">Permisos</x-button>
                        </a>
                    </div>
                </x-card>
            @empty
                <div class="col-span-full">
                    <x-card class="text-center py-12">
                        <x-icon name="shield-check" class="w-12 h-12 text-secondary-300 mx-auto" />
                        <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay roles</h3>
                        <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo rol.</p>
                        @can('create', \Modules\AccessControl\Entities\Role::class)
                            <div class="mt-4">
                                <a href="{{ route('accesscontrol.roles.create') }}">
                                    <x-button icon="plus">Nuevo Rol</x-button>
                                </a>
                            </div>
                        @endcan
                    </x-card>
                </div>
            @endforelse
        </div>

        @if($roles->hasPages())
            <div class="mt-6">
                {{ $roles->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
