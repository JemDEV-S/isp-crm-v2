@extends('layouts.app')

@section('title', 'Crear Rol')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('accesscontrol.roles.index') }}" class="text-secondary-500 hover:text-secondary-700">Roles</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Crear Rol</span>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Rol</h1>
            <p class="mt-1 text-sm text-secondary-500">Configure un nuevo rol con sus permisos</p>
        </div>

        <form action="{{ route('accesscontrol.roles.store') }}" method="POST">
            @csrf

            <x-card title="Información del Rol" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="code"
                        label="Código"
                        :value="old('code')"
                        :error="$errors->first('code')"
                        required
                        placeholder="ej: supervisor_ventas"
                        hint="Solo letras minúsculas, números y guiones bajos"
                    />

                    <x-input
                        name="name"
                        label="Nombre"
                        :value="old('name')"
                        :error="$errors->first('name')"
                        required
                        placeholder="Ej: Supervisor de Ventas"
                    />

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Descripción
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            class="block w-full rounded-lg border-secondary-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-secondary-900"
                            placeholder="Describe las responsabilidades de este rol..."
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-secondary-700">Rol activo</span>
                    </label>
                </div>
            </x-card>

            <x-card title="Permisos">
                <p class="text-sm text-secondary-500 mb-4">Seleccione los permisos que tendrá este rol</p>

                <div x-data="{ expanded: {} }" class="space-y-4">
                    @foreach($permissions as $module => $modulePermissions)
                        <div class="border border-secondary-200 rounded-lg overflow-hidden">
                            <button
                                type="button"
                                @click="expanded['{{ $module }}'] = !expanded['{{ $module }}']"
                                class="w-full flex items-center justify-between px-4 py-3 bg-secondary-50 hover:bg-secondary-100 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <x-icon name="folder" class="w-5 h-5 text-secondary-500" />
                                    <span class="font-medium text-secondary-900 capitalize">{{ $module }}</span>
                                    <x-badge variant="default" size="sm">{{ count($modulePermissions) }}</x-badge>
                                </div>
                                <svg class="w-5 h-5 text-secondary-500 transition-transform" :class="expanded['{{ $module }}'] && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="expanded['{{ $module }}']" x-collapse class="px-4 py-3 bg-white">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($modulePermissions as $permission)
                                        <label class="flex items-start p-3 rounded border border-secondary-200 hover:border-primary-300 cursor-pointer transition-colors">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission['id'] }}"
                                                   {{ in_array($permission['id'], old('permissions', [])) ? 'checked' : '' }}
                                                   class="mt-0.5 rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            <div class="ml-3">
                                                <span class="block text-sm font-medium text-secondary-900">{{ $permission['name'] }}</span>
                                                <span class="block text-xs text-secondary-500 font-mono">{{ $permission['code'] }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('accesscontrol.roles.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Crear Rol</x-button>
            </div>
        </form>
    </div>
@endsection
