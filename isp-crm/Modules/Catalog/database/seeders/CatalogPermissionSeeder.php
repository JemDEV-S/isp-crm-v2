<?php

declare(strict_types=1);

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AccessControl\Entities\Permission;
use Modules\AccessControl\Entities\Role;

class CatalogPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Plans
            ['code' => 'catalog.plan.view', 'name' => 'Ver planes', 'module' => 'Catalog', 'description' => 'Permite ver la lista de planes'],
            ['code' => 'catalog.plan.create', 'name' => 'Crear planes', 'module' => 'Catalog', 'description' => 'Permite crear nuevos planes'],
            ['code' => 'catalog.plan.update', 'name' => 'Editar planes', 'module' => 'Catalog', 'description' => 'Permite editar planes existentes'],
            ['code' => 'catalog.plan.delete', 'name' => 'Eliminar planes', 'module' => 'Catalog', 'description' => 'Permite eliminar planes'],

            // Promotions
            ['code' => 'catalog.promotion.view', 'name' => 'Ver promociones', 'module' => 'Catalog', 'description' => 'Permite ver la lista de promociones'],
            ['code' => 'catalog.promotion.create', 'name' => 'Crear promociones', 'module' => 'Catalog', 'description' => 'Permite crear nuevas promociones'],
            ['code' => 'catalog.promotion.update', 'name' => 'Editar promociones', 'module' => 'Catalog', 'description' => 'Permite editar promociones existentes'],
            ['code' => 'catalog.promotion.delete', 'name' => 'Eliminar promociones', 'module' => 'Catalog', 'description' => 'Permite eliminar promociones'],
            ['code' => 'catalog.promotion.apply', 'name' => 'Aplicar promociones', 'module' => 'Catalog', 'description' => 'Permite aplicar promociones a suscripciones'],

            // Addons
            ['code' => 'catalog.addon.view', 'name' => 'Ver addons', 'module' => 'Catalog', 'description' => 'Permite ver la lista de addons'],
            ['code' => 'catalog.addon.create', 'name' => 'Crear addons', 'module' => 'Catalog', 'description' => 'Permite crear nuevos addons'],
            ['code' => 'catalog.addon.update', 'name' => 'Editar addons', 'module' => 'Catalog', 'description' => 'Permite editar addons existentes'],
            ['code' => 'catalog.addon.delete', 'name' => 'Eliminar addons', 'module' => 'Catalog', 'description' => 'Permite eliminar addons'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['code' => $permissionData['code']],
                $permissionData
            );
        }

        // Asignar permisos a roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to roles.
     */
    private function assignPermissionsToRoles(): void
    {
        // Superadmin y Admin tienen todos los permisos
        $adminRoles = Role::whereIn('code', ['superadmin', 'admin'])->get();
        $allPermissions = Permission::where('module', 'Catalog')->pluck('id');

        foreach ($adminRoles as $role) {
            $role->permissions()->syncWithoutDetaching($allPermissions);
        }

        // Supervisor puede ver todo y aplicar promociones
        $supervisor = Role::where('code', 'supervisor')->first();
        if ($supervisor) {
            $supervisorPermissions = Permission::where('module', 'Catalog')
                ->whereIn('code', [
                    'catalog.plan.view',
                    'catalog.promotion.view',
                    'catalog.promotion.apply',
                    'catalog.addon.view',
                ])
                ->pluck('id');
            $supervisor->permissions()->syncWithoutDetaching($supervisorPermissions);
        }

        // Ventas puede ver planes, promociones, addons y aplicar promociones
        $sales = Role::where('code', 'sales')->first();
        if ($sales) {
            $salesPermissions = Permission::where('module', 'Catalog')
                ->whereIn('code', [
                    'catalog.plan.view',
                    'catalog.promotion.view',
                    'catalog.promotion.apply',
                    'catalog.addon.view',
                ])
                ->pluck('id');
            $sales->permissions()->syncWithoutDetaching($salesPermissions);
        }

        // Soporte puede ver planes y addons
        $support = Role::where('code', 'support')->first();
        if ($support) {
            $supportPermissions = Permission::where('module', 'Catalog')
                ->whereIn('code', [
                    'catalog.plan.view',
                    'catalog.addon.view',
                ])
                ->pluck('id');
            $support->permissions()->syncWithoutDetaching($supportPermissions);
        }
    }
}
