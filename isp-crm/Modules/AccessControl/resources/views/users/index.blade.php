@extends('layouts.app')

@section('title', 'Usuarios')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Usuarios</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Usuarios</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona los usuarios del sistema</p>
            </div>
            @can('create', \Modules\AccessControl\Entities\User::class)
                <a href="{{ route('accesscontrol.users.create') }}">
                    <x-button icon="plus">
                        Nuevo Usuario
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('accesscontrol.users.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre, email o teléfono..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="zone_id" label="Zona" placeholder="Todas las zonas">
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ ($filters['zone_id'] ?? '') == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="role_id" label="Rol" placeholder="Todos los roles">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ ($filters['role_id'] ?? '') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <x-select name="is_active" label="Estado" placeholder="Todos">
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </x-select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('accesscontrol.users.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Table -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Usuario
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Contacto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Roles
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Zona
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($users as $user)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                        <span class="text-sm font-semibold text-primary-700">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-secondary-900">{{ $user->name }}</div>
                                    <div class="text-xs text-secondary-500">
                                        Último acceso: {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Nunca' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">{{ $user->email }}</div>
                            <div class="text-sm text-secondary-500">{{ $user->phone ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles as $role)
                                    <x-badge variant="primary" size="sm">{{ $role->name }}</x-badge>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                            {{ $user->zone?->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$user->is_active ? 'success' : 'danger'" dot>
                                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('accesscontrol.users.show', $user) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('update', $user)
                                    <a href="{{ route('accesscontrol.users.edit', $user) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('delete', $user)
                                    <form action="{{ route('accesscontrol.users.destroy', $user) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este usuario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger-600 hover:text-danger-900">
                                            <x-icon name="trash" class="w-5 h-5" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="users" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay usuarios</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo usuario.</p>
                                @can('create', \Modules\AccessControl\Entities\User::class)
                                    <div class="mt-4">
                                        <a href="{{ route('accesscontrol.users.create') }}">
                                            <x-button icon="plus">Nuevo Usuario</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $users->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
