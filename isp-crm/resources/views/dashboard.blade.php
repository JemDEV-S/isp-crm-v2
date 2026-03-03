@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="text-secondary-500">Dashboard</span>
@endsection

@section('quick-actions')
    <x-button variant="primary" icon="plus" size="sm">
        Nueva Instalación
    </x-button>
@endsection

@section('content')
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <x-stat-card
            title="Clientes Activos"
            value="1,247"
            icon="users"
            variant="primary"
            trend="+12.5% vs mes anterior"
            :trend-up="true"
        />

        <x-stat-card
            title="Ingresos del Mes"
            value="S/ 89,450"
            icon="currency"
            variant="success"
            trend="+8.2% vs mes anterior"
            :trend-up="true"
        />

        <x-stat-card
            title="Órdenes Pendientes"
            value="23"
            icon="clipboard"
            variant="warning"
        />

        <x-stat-card
            title="Tickets Abiertos"
            value="12"
            icon="exclamation-circle"
            variant="danger"
            trend="-15% vs semana anterior"
            :trend-up="true"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Recent Orders -->
        <x-card title="Órdenes Recientes">
            <x-slot name="actions">
                <x-button variant="ghost" size="sm">
                    Ver todas
                </x-button>
            </x-slot>

            <x-table :headers="['Cliente', 'Tipo', 'Estado', 'Fecha']">
                <tr class="hover:bg-secondary-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-sm font-semibold text-primary-700">JD</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-secondary-900">Juan Díaz</div>
                                <div class="text-sm text-secondary-500">DNI: 12345678</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-secondary-900">Instalación</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <x-badge variant="warning" dot>Programado</x-badge>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                        08/12/2025
                    </td>
                </tr>
                <tr class="hover:bg-secondary-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-sm font-semibold text-primary-700">MG</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-secondary-900">María García</div>
                                <div class="text-sm text-secondary-500">DNI: 87654321</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-secondary-900">Reparación</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <x-badge variant="info" dot>En Progreso</x-badge>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                        07/12/2025
                    </td>
                </tr>
                <tr class="hover:bg-secondary-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-sm font-semibold text-primary-700">CR</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-secondary-900">Carlos Rodríguez</div>
                                <div class="text-sm text-secondary-500">RUC: 20123456789</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-secondary-900">Instalación</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <x-badge variant="success" dot>Completado</x-badge>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                        06/12/2025
                    </td>
                </tr>
            </x-table>
        </x-card>

        <!-- Pending Payments -->
        <x-card title="Pagos Pendientes">
            <x-slot name="actions">
                <x-button variant="ghost" size="sm">
                    Ver todos
                </x-button>
            </x-slot>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-secondary-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-danger-100 flex items-center justify-center">
                            <x-icon name="currency" class="h-5 w-5 text-danger-600" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-secondary-900">Pedro Sánchez</p>
                            <p class="text-xs text-secondary-500">Vencido hace 15 días</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-secondary-900">S/ 89.90</p>
                        <x-badge variant="danger" size="sm">Vencido</x-badge>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-secondary-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-warning-100 flex items-center justify-center">
                            <x-icon name="currency" class="h-5 w-5 text-warning-600" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-secondary-900">Ana López</p>
                            <p class="text-xs text-secondary-500">Vence en 3 días</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-secondary-900">S/ 129.90</p>
                        <x-badge variant="warning" size="sm">Por vencer</x-badge>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-secondary-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-info-100 flex items-center justify-center">
                            <x-icon name="currency" class="h-5 w-5 text-info-600" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-secondary-900">Luis Torres</p>
                            <p class="text-xs text-secondary-500">Vence en 10 días</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-secondary-900">S/ 89.90</p>
                        <x-badge variant="info" size="sm">Pendiente</x-badge>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Quick Actions -->
    <x-card title="Acciones Rápidas">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <button class="flex flex-col items-center justify-center p-6 bg-secondary-50 rounded-lg hover:bg-secondary-100 transition-colors">
                <div class="w-12 h-12 rounded-lg bg-primary-500 bg-opacity-10 flex items-center justify-center mb-3">
                    <x-icon name="users" class="h-6 w-6 text-primary-600" />
                </div>
                <span class="text-sm font-medium text-secondary-900">Nuevo Cliente</span>
            </button>

            <button class="flex flex-col items-center justify-center p-6 bg-secondary-50 rounded-lg hover:bg-secondary-100 transition-colors">
                <div class="w-12 h-12 rounded-lg bg-success-500 bg-opacity-10 flex items-center justify-center mb-3">
                    <x-icon name="clipboard" class="h-6 w-6 text-success-600" />
                </div>
                <span class="text-sm font-medium text-secondary-900">Nueva Orden</span>
            </button>

            <button class="flex flex-col items-center justify-center p-6 bg-secondary-50 rounded-lg hover:bg-secondary-100 transition-colors">
                <div class="w-12 h-12 rounded-lg bg-warning-500 bg-opacity-10 flex items-center justify-center mb-3">
                    <x-icon name="currency" class="h-6 w-6 text-warning-600" />
                </div>
                <span class="text-sm font-medium text-secondary-900">Registrar Pago</span>
            </button>

            <button class="flex flex-col items-center justify-center p-6 bg-secondary-50 rounded-lg hover:bg-secondary-100 transition-colors">
                <div class="w-12 h-12 rounded-lg bg-info-500 bg-opacity-10 flex items-center justify-center mb-3">
                    <x-icon name="chart" class="h-6 w-6 text-info-600" />
                </div>
                <span class="text-sm font-medium text-secondary-900">Ver Reportes</span>
            </button>
        </div>
    </x-card>
@endsection
