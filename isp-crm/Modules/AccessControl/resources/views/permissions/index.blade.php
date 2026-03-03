@extends('layouts.app')

@section('title', 'Permisos')

@section('breadcrumb')
    <span class="text-secondary-500">Control de Acceso</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Permisos</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Permisos</h1>
                <p class="mt-1 text-sm text-secondary-500">Vista general de todos los permisos del sistema</p>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="lg">
                    {{ $permissions->total() }} permisos totales
                </x-badge>
            </div>
        </div>

        <!-- Filters -->
        <x-card>
            <form method="GET" action="{{ route('accesscontrol.permissions.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre o código..."
                        :value="$search ?? ''"
                        icon="search"
                    />
                </div>

                <div class="w-48">
                    <x-select name="module" label="Módulo" placeholder="Todos los módulos">
                        @foreach($modules as $mod)
                            <option value="{{ $mod }}" {{ ($module ?? '') == $mod ? 'selected' : '' }}>
                                {{ ucfirst($mod) }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('accesscontrol.permissions.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Permissions by Module -->
        @php
            $permissionsByModule = $permissions->groupBy('module');
        @endphp

        <div class="space-y-6">
            @forelse($permissionsByModule as $moduleName => $modulePermissions)
                <x-card :padding="false">
                    <div class="px-6 py-4 bg-secondary-50 border-b border-secondary-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                                <x-icon name="folder" class="w-5 h-5 text-primary-600" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-secondary-900 capitalize">{{ $moduleName }}</h2>
                                <p class="text-sm text-secondary-500">{{ $modulePermissions->count() }} permisos</p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-secondary-200">
                            <thead class="bg-white">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                                        Código
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                                        Nombre
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                                        Descripción
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                                        Roles
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-secondary-200">
                                @foreach($modulePermissions as $permission)
                                    <tr class="hover:bg-secondary-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <code class="text-sm bg-secondary-100 px-2 py-1 rounded text-secondary-700">
                                                {{ $permission->code }}
                                            </code>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-secondary-900">{{ $permission->name }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-secondary-500">{{ $permission->description ?? '-' }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $rolesCount = $permission->roles()->count();
                                            @endphp
                                            <x-badge variant="{{ $rolesCount > 0 ? 'primary' : 'default' }}">
                                                {{ $rolesCount }} rol{{ $rolesCount !== 1 ? 'es' : '' }}
                                            </x-badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @empty
                <x-card class="text-center py-12">
                    <x-icon name="key" class="w-12 h-12 text-secondary-300 mx-auto" />
                    <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay permisos</h3>
                    <p class="mt-1 text-sm text-secondary-500">No se encontraron permisos con los filtros aplicados.</p>
                </x-card>
            @endforelse
        </div>

        @if($permissions->hasPages())
            <div class="mt-6">
                {{ $permissions->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
