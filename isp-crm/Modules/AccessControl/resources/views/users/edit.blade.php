@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('accesscontrol.users.index') }}" class="text-secondary-500 hover:text-secondary-700">Usuarios</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Editar Usuario</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Usuario</h1>
            <p class="mt-1 text-sm text-secondary-500">Modifique la información del usuario</p>
        </div>

        <form action="{{ route('accesscontrol.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <x-card title="Información Personal" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="name"
                        label="Nombre completo"
                        :value="old('name', $user->name)"
                        :error="$errors->first('name')"
                        required
                    />

                    <x-input
                        type="email"
                        name="email"
                        label="Correo electrónico"
                        :value="old('email', $user->email)"
                        :error="$errors->first('email')"
                        required
                    />

                    <x-input
                        name="phone"
                        label="Teléfono"
                        :value="old('phone', $user->phone)"
                        :error="$errors->first('phone')"
                    />

                    <x-select
                        name="zone_id"
                        label="Zona"
                        :error="$errors->first('zone_id')"
                        placeholder="Seleccione una zona"
                    >
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ old('zone_id', $user->zone_id) == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
            </x-card>

            <x-card title="Cambiar Contraseña" subtitle="Deje en blanco para mantener la contraseña actual" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        type="password"
                        name="password"
                        label="Nueva Contraseña"
                        :error="$errors->first('password')"
                        placeholder="Mínimo 8 caracteres"
                    />

                    <x-input
                        type="password"
                        name="password_confirmation"
                        label="Confirmar Contraseña"
                        placeholder="Repita la contraseña"
                    />
                </div>

                <div class="mt-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                               class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Usuario activo</span>
                    </label>
                </div>
            </x-card>

            <x-card title="Roles y Permisos">
                <p class="text-sm text-secondary-500 mb-4">Seleccione al menos un rol para el usuario</p>

                @error('roles')
                    <x-alert variant="danger" class="mb-4">{{ $message }}</x-alert>
                @enderror

                @php
                    $userRoleIds = $user->roles->pluck('id')->toArray();
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($roles as $role)
                        <label class="relative flex items-start p-4 rounded-lg border border-secondary-200 hover:border-primary-300 cursor-pointer transition-colors">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                       {{ in_array($role->id, old('roles', $userRoleIds)) ? 'checked' : '' }}
                                       class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-secondary-900">{{ $role->name }}</span>
                                @if($role->description)
                                    <span class="block text-xs text-secondary-500">{{ $role->description }}</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </x-card>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('accesscontrol.users.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
