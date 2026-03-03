<?php

declare(strict_types=1);

namespace Modules\AccessControl\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AccessControl\Entities\Permission;
use Modules\AccessControl\Entities\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = $this->getPermissions();

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['code' => $permissionData['code']],
                $permissionData
            );
        }

        $this->command->info('✓ Permisos creados exitosamente');

        // Asignar todos los permisos al rol superadmin
        $this->assignPermissionsToSuperAdmin();
    }

    /**
     * Get all permissions for the system.
     */
    private function getPermissions(): array
    {
        return [
            // ========================================
            // ACCESS CONTROL MODULE
            // ========================================
            [
                'code' => 'accesscontrol.user.view',
                'name' => 'Ver usuarios',
                'module' => 'accesscontrol',
                'description' => 'Ver listado de usuarios',
            ],
            [
                'code' => 'accesscontrol.user.view.zone',
                'name' => 'Ver usuarios de zona',
                'module' => 'accesscontrol',
                'description' => 'Ver solo usuarios de su zona',
            ],
            [
                'code' => 'accesscontrol.user.create',
                'name' => 'Crear usuarios',
                'module' => 'accesscontrol',
                'description' => 'Crear nuevos usuarios',
            ],
            [
                'code' => 'accesscontrol.user.update',
                'name' => 'Actualizar usuarios',
                'module' => 'accesscontrol',
                'description' => 'Editar información de usuarios',
            ],
            [
                'code' => 'accesscontrol.user.delete',
                'name' => 'Eliminar usuarios',
                'module' => 'accesscontrol',
                'description' => 'Eliminar usuarios del sistema',
            ],
            [
                'code' => 'accesscontrol.role.view',
                'name' => 'Ver roles',
                'module' => 'accesscontrol',
                'description' => 'Ver listado de roles',
            ],
            [
                'code' => 'accesscontrol.role.create',
                'name' => 'Crear roles',
                'module' => 'accesscontrol',
                'description' => 'Crear nuevos roles',
            ],
            [
                'code' => 'accesscontrol.role.update',
                'name' => 'Actualizar roles',
                'module' => 'accesscontrol',
                'description' => 'Editar roles',
            ],
            [
                'code' => 'accesscontrol.role.delete',
                'name' => 'Eliminar roles',
                'module' => 'accesscontrol',
                'description' => 'Eliminar roles no sistémicos',
            ],
            [
                'code' => 'accesscontrol.permission.view',
                'name' => 'Ver permisos',
                'module' => 'accesscontrol',
                'description' => 'Ver listado de permisos',
            ],
            [
                'code' => 'accesscontrol.permission.assign',
                'name' => 'Asignar permisos',
                'module' => 'accesscontrol',
                'description' => 'Asignar permisos a roles',
            ],
            [
                'code' => 'accesscontrol.zone.view',
                'name' => 'Ver zonas',
                'module' => 'accesscontrol',
                'description' => 'Ver listado de zonas',
            ],
            [
                'code' => 'accesscontrol.zone.create',
                'name' => 'Crear zonas',
                'module' => 'accesscontrol',
                'description' => 'Crear nuevas zonas',
            ],
            [
                'code' => 'accesscontrol.zone.update',
                'name' => 'Actualizar zonas',
                'module' => 'accesscontrol',
                'description' => 'Editar zonas',
            ],
            [
                'code' => 'accesscontrol.zone.delete',
                'name' => 'Eliminar zonas',
                'module' => 'accesscontrol',
                'description' => 'Eliminar zonas',
            ],

            // ========================================
            // CRM MODULE
            // ========================================
            [
                'code' => 'crm.lead.view',
                'name' => 'Ver prospectos',
                'module' => 'crm',
                'description' => 'Ver todos los prospectos',
            ],
            [
                'code' => 'crm.lead.view.own',
                'name' => 'Ver prospectos propios',
                'module' => 'crm',
                'description' => 'Ver solo prospectos asignados',
            ],
            [
                'code' => 'crm.lead.view.zone',
                'name' => 'Ver prospectos de zona',
                'module' => 'crm',
                'description' => 'Ver prospectos de su zona',
            ],
            [
                'code' => 'crm.lead.create',
                'name' => 'Crear prospectos',
                'module' => 'crm',
                'description' => 'Crear nuevos prospectos',
            ],
            [
                'code' => 'crm.lead.update',
                'name' => 'Actualizar prospectos',
                'module' => 'crm',
                'description' => 'Editar información de prospectos',
            ],
            [
                'code' => 'crm.lead.delete',
                'name' => 'Eliminar prospectos',
                'module' => 'crm',
                'description' => 'Eliminar prospectos',
            ],
            [
                'code' => 'crm.lead.convert',
                'name' => 'Convertir prospectos',
                'module' => 'crm',
                'description' => 'Convertir prospecto a cliente',
            ],
            [
                'code' => 'crm.customer.view',
                'name' => 'Ver clientes',
                'module' => 'crm',
                'description' => 'Ver todos los clientes',
            ],
            [
                'code' => 'crm.customer.view.zone',
                'name' => 'Ver clientes de zona',
                'module' => 'crm',
                'description' => 'Ver clientes de su zona',
            ],
            [
                'code' => 'crm.customer.create',
                'name' => 'Crear clientes',
                'module' => 'crm',
                'description' => 'Crear nuevos clientes',
            ],
            [
                'code' => 'crm.customer.update',
                'name' => 'Actualizar clientes',
                'module' => 'crm',
                'description' => 'Editar información de clientes',
            ],
            [
                'code' => 'crm.customer.delete',
                'name' => 'Eliminar clientes',
                'module' => 'crm',
                'description' => 'Eliminar clientes',
            ],

            // ========================================
            // SUBSCRIPTION MODULE
            // ========================================
            [
                'code' => 'subscription.contract.view',
                'name' => 'Ver contratos',
                'module' => 'subscription',
                'description' => 'Ver todos los contratos',
            ],
            [
                'code' => 'subscription.contract.view.zone',
                'name' => 'Ver contratos de zona',
                'module' => 'subscription',
                'description' => 'Ver contratos de su zona',
            ],
            [
                'code' => 'subscription.contract.create',
                'name' => 'Crear contratos',
                'module' => 'subscription',
                'description' => 'Crear nuevos contratos',
            ],
            [
                'code' => 'subscription.contract.update',
                'name' => 'Actualizar contratos',
                'module' => 'subscription',
                'description' => 'Editar contratos',
            ],
            [
                'code' => 'subscription.contract.cancel',
                'name' => 'Cancelar contratos',
                'module' => 'subscription',
                'description' => 'Cancelar servicios',
            ],
            [
                'code' => 'subscription.contract.suspend',
                'name' => 'Suspender contratos',
                'module' => 'subscription',
                'description' => 'Suspender servicios',
            ],
            [
                'code' => 'subscription.contract.override_price',
                'name' => 'Sobrescribir precios',
                'module' => 'subscription',
                'description' => 'Modificar precios de contratos',
            ],

            // ========================================
            // FIELDOPS MODULE
            // ========================================
            [
                'code' => 'fieldops.workorder.view',
                'name' => 'Ver órdenes de trabajo',
                'module' => 'fieldops',
                'description' => 'Ver todas las órdenes',
            ],
            [
                'code' => 'fieldops.workorder.view.own',
                'name' => 'Ver órdenes propias',
                'module' => 'fieldops',
                'description' => 'Ver solo órdenes asignadas',
            ],
            [
                'code' => 'fieldops.workorder.view.zone',
                'name' => 'Ver órdenes de zona',
                'module' => 'fieldops',
                'description' => 'Ver órdenes de su zona',
            ],
            [
                'code' => 'fieldops.workorder.create',
                'name' => 'Crear órdenes',
                'module' => 'fieldops',
                'description' => 'Crear órdenes de trabajo',
            ],
            [
                'code' => 'fieldops.workorder.assign',
                'name' => 'Asignar órdenes',
                'module' => 'fieldops',
                'description' => 'Asignar órdenes a técnicos',
            ],
            [
                'code' => 'fieldops.workorder.start',
                'name' => 'Iniciar órdenes',
                'module' => 'fieldops',
                'description' => 'Iniciar ejecución de órdenes',
            ],
            [
                'code' => 'fieldops.workorder.complete',
                'name' => 'Completar órdenes',
                'module' => 'fieldops',
                'description' => 'Marcar órdenes como completadas',
            ],
            [
                'code' => 'fieldops.workorder.cancel',
                'name' => 'Cancelar órdenes',
                'module' => 'fieldops',
                'description' => 'Cancelar órdenes de trabajo',
            ],
            [
                'code' => 'fieldops.workorder.reassign',
                'name' => 'Reasignar órdenes',
                'module' => 'fieldops',
                'description' => 'Cambiar técnico asignado',
            ],

            // ========================================
            // FINANCE MODULE
            // ========================================
            [
                'code' => 'finance.invoice.view',
                'name' => 'Ver facturas',
                'module' => 'finance',
                'description' => 'Ver todas las facturas',
            ],
            [
                'code' => 'finance.invoice.view.zone',
                'name' => 'Ver facturas de zona',
                'module' => 'finance',
                'description' => 'Ver facturas de su zona',
            ],
            [
                'code' => 'finance.invoice.create',
                'name' => 'Crear facturas',
                'module' => 'finance',
                'description' => 'Generar facturas',
            ],
            [
                'code' => 'finance.invoice.void',
                'name' => 'Anular facturas',
                'module' => 'finance',
                'description' => 'Anular facturas emitidas',
            ],
            [
                'code' => 'finance.payment.view',
                'name' => 'Ver pagos',
                'module' => 'finance',
                'description' => 'Ver registro de pagos',
            ],
            [
                'code' => 'finance.payment.register',
                'name' => 'Registrar pagos',
                'module' => 'finance',
                'description' => 'Registrar pagos de clientes',
            ],
            [
                'code' => 'finance.payment.void',
                'name' => 'Anular pagos',
                'module' => 'finance',
                'description' => 'Anular pagos registrados',
            ],

            // ========================================
            // NETWORK MODULE
            // ========================================
            [
                'code' => 'network.device.view',
                'name' => 'Ver dispositivos',
                'module' => 'network',
                'description' => 'Ver infraestructura de red',
            ],
            [
                'code' => 'network.device.create',
                'name' => 'Crear dispositivos',
                'module' => 'network',
                'description' => 'Agregar dispositivos de red',
            ],
            [
                'code' => 'network.device.configure',
                'name' => 'Configurar dispositivos',
                'module' => 'network',
                'description' => 'Configurar equipos de red',
            ],
            [
                'code' => 'network.ip.assign',
                'name' => 'Asignar IPs',
                'module' => 'network',
                'description' => 'Asignar direcciones IP',
            ],
            [
                'code' => 'network.ip.release',
                'name' => 'Liberar IPs',
                'module' => 'network',
                'description' => 'Liberar direcciones IP',
            ],

            // ========================================
            // INVENTORY MODULE
            // ========================================
            [
                'code' => 'inventory.stock.view',
                'name' => 'Ver inventario',
                'module' => 'inventory',
                'description' => 'Ver todo el inventario',
            ],
            [
                'code' => 'inventory.stock.view.own',
                'name' => 'Ver inventario propio',
                'module' => 'inventory',
                'description' => 'Ver su bodega móvil',
            ],
            [
                'code' => 'inventory.movement.create',
                'name' => 'Crear movimientos',
                'module' => 'inventory',
                'description' => 'Registrar movimientos de inventario',
            ],
            [
                'code' => 'inventory.movement.approve',
                'name' => 'Aprobar movimientos',
                'module' => 'inventory',
                'description' => 'Aprobar transferencias',
            ],
            [
                'code' => 'inventory.serial.assign',
                'name' => 'Asignar seriales',
                'module' => 'inventory',
                'description' => 'Asignar equipos con serial',
            ],
            [
                'code' => 'inventory.serial.transfer',
                'name' => 'Transferir seriales',
                'module' => 'inventory',
                'description' => 'Transferir equipos entre almacenes',
            ],
        ];
    }

    /**
     * Assign all permissions to superadmin role.
     */
    private function assignPermissionsToSuperAdmin(): void
    {
        $superAdmin = Role::where('code', 'superadmin')->first();

        if ($superAdmin) {
            $allPermissions = Permission::all()->pluck('id')->toArray();
            $superAdmin->syncPermissions($allPermissions);
            $this->command->info('✓ Permisos asignados al rol superadmin');
        }
    }
}
