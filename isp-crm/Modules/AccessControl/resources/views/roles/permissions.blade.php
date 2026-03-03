@extends('layouts.app')

@section('title', 'Permisos del Rol')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('accesscontrol.roles.index') }}" class="text-secondary-500 hover:text-secondary-700">Roles</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('accesscontrol.roles.show', $role) }}" class="text-secondary-500 hover:text-secondary-700">{{ $role->name }}</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Permisos</span>
@endsection

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Permisos de {{ $role->name }}</h1>
                <p class="mt-1 text-sm text-secondary-500">
                    Gestiona los permisos asignados a este rol
                    @if($role->is_system && $role->code !== 'superadmin')
                        <x-badge variant="warning" class="ml-2">Rol del sistema - Edición limitada</x-badge>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="lg">
                    {{ $role->permissions->count() }} permisos asignados
                </x-badge>
            </div>
        </div>

        <form action="{{ route('accesscontrol.roles.sync-permissions', $role) }}" method="POST">
            @csrf

            @php
                $rolePermissionIds = $role->permissions->pluck('id')->toArray();
            @endphp

            <div
                x-data="{
                    expanded: {},
                    selectAll(module, checkboxes) {
                        const checked = !this.allSelected(checkboxes);
                        checkboxes.forEach(id => {
                            document.getElementById('permission_' + id).checked = checked;
                        });
                    },
                    allSelected(checkboxes) {
                        return checkboxes.every(id => document.getElementById('permission_' + id).checked);
                    }
                }"
                class="space-y-4"
            >
                @foreach($permissions as $module => $modulePermissions)
                    @php
                        $modulePermissionIds = collect($modulePermissions)->pluck('id')->toArray();
                    @endphp
                    <x-card :padding="false">
                        <div class="px-4 py-3 bg-secondary-50 flex items-center justify-between border-b border-secondary-200">
                            <button
                                type="button"
                                @click="expanded['{{ $module }}'] = !expanded['{{ $module }}']"
                                class="flex items-center gap-3 hover:text-primary-600"
                            >
                                <svg class="w-5 h-5 text-secondary-500 transition-transform" :class="expanded['{{ $module }}'] && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <x-icon name="folder" class="w-5 h-5 text-secondary-500" />
                                <span class="font-medium text-secondary-900 capitalize">{{ $module }}</span>
                                <x-badge variant="default" size="sm">
                                    {{ count(array_intersect($modulePermissionIds, $rolePermissionIds)) }}/{{ count($modulePermissions) }}
                                </x-badge>
                            </button>
                            <button
                                type="button"
                                @click="selectAll('{{ $module }}', {{ json_encode($modulePermissionIds) }})"
                                class="text-sm text-primary-600 hover:text-primary-700"
                            >
                                Seleccionar todos
                            </button>
                        </div>
                        <div x-show="expanded['{{ $module }}']" x-collapse class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($modulePermissions as $permission)
                                    <label class="flex items-start p-3 rounded border border-secondary-200 hover:border-primary-300 cursor-pointer transition-colors group">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission['id'] }}"
                                            id="permission_{{ $permission['id'] }}"
                                            {{ in_array($permission['id'], $rolePermissionIds) ? 'checked' : '' }}
                                            class="mt-0.5 rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        >
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-secondary-900 group-hover:text-primary-700">
                                                {{ $permission['name'] }}
                                            </span>
                                            <span class="block text-xs text-secondary-500 font-mono">{{ $permission['code'] }}</span>
                                            @if(!empty($permission['description']))
                                                <span class="block text-xs text-secondary-400 mt-1">{{ $permission['description'] }}</span>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end gap-3 sticky bottom-6">
                <a href="{{ route('accesscontrol.roles.show', $role) }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Permisos</x-button>
            </div>
        </form>
    </div>
@endsection
