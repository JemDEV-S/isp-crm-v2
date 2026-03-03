@extends('layouts.app')

@section('title', 'Detalle de Usuario')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('accesscontrol.users.index') }}" class="text-secondary-500 hover:text-secondary-700">Usuarios</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $user->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 rounded-full bg-primary-100 flex items-center justify-center">
                    <span class="text-2xl font-semibold text-primary-700">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900">{{ $user->name }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <x-badge :variant="$user->is_active ? 'success' : 'danger'" dot>
                            {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                        </x-badge>
                        @foreach($user->roles as $role)
                            <x-badge variant="primary">{{ $role->name }}</x-badge>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                @can('update', $user)
                    <a href="{{ route('accesscontrol.users.edit', $user) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
                <form action="{{ route('accesscontrol.users.toggle-status', $user) }}" method="POST" class="inline">
                    @csrf
                    <x-button type="submit" :variant="$user->is_active ? 'warning' : 'success'">
                        {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                    </x-button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Información Personal">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Correo electrónico</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Teléfono</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $user->phone ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Zona</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $user->zone?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">UUID</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono text-xs">{{ $user->uuid }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Roles Asignados">
                    @if($user->roles->count() > 0)
                        <div class="space-y-3">
                            @foreach($user->roles as $role)
                                <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                    <div>
                                        <span class="font-medium text-secondary-900">{{ $role->name }}</span>
                                        @if($role->description)
                                            <p class="text-sm text-secondary-500">{{ $role->description }}</p>
                                        @endif
                                    </div>
                                    <div class="text-xs text-secondary-400">
                                        {{ $role->permissions->count() }} permisos
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-secondary-500">No tiene roles asignados</p>
                    @endif
                </x-card>

                <x-card title="Sesiones Recientes">
                    @if($user->sessions->count() > 0)
                        <div class="space-y-3">
                            @foreach($user->sessions as $session)
                                <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                    <div>
                                        <span class="text-sm text-secondary-900">{{ $session->ip_address }}</span>
                                        <p class="text-xs text-secondary-500">{{ Str::limit($session->user_agent, 50) }}</p>
                                    </div>
                                    <div class="text-xs text-secondary-400">
                                        {{ $session->last_activity->diffForHumans() }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-secondary-500">No hay sesiones registradas</p>
                    @endif
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <x-card title="Información de Cuenta">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $user->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $user->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Último acceso</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Nunca' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Email verificado</dt>
                            <dd class="mt-1">
                                <x-badge :variant="$user->email_verified_at ? 'success' : 'warning'">
                                    {{ $user->email_verified_at ? 'Verificado' : 'No verificado' }}
                                </x-badge>
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('update', $user)
                            <a href="{{ route('accesscontrol.users.edit', $user) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar Usuario</x-button>
                            </a>
                        @endcan
                        @can('delete', $user)
                            <form action="{{ route('accesscontrol.users.destroy', $user) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                    Eliminar Usuario
                                </x-button>
                            </form>
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
