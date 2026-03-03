<?php

declare(strict_types=1);

namespace Modules\AccessControl\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AccessControl\Entities\Role;
use Modules\AccessControl\Enums\RoleCode;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'code' => RoleCode::SUPERADMIN->value,
                'name' => RoleCode::SUPERADMIN->label(),
                'description' => 'Acceso total al sistema sin restricciones',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'code' => RoleCode::ADMIN->value,
                'name' => RoleCode::ADMIN->label(),
                'description' => 'Administrador con acceso completo a funcionalidades',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'code' => RoleCode::SUPERVISOR->value,
                'name' => RoleCode::SUPERVISOR->label(),
                'description' => 'Supervisor con permisos de gestión y aprobación',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'code' => RoleCode::TECHNICIAN->value,
                'name' => RoleCode::TECHNICIAN->label(),
                'description' => 'Técnico de campo para instalaciones y reparaciones',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'code' => RoleCode::SALES->value,
                'name' => RoleCode::SALES->label(),
                'description' => 'Personal de ventas para gestión comercial',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'code' => RoleCode::BILLING->value,
                'name' => RoleCode::BILLING->label(),
                'description' => 'Personal de facturación y cobranza',
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'code' => RoleCode::SUPPORT->value,
                'name' => RoleCode::SUPPORT->label(),
                'description' => 'Soporte técnico al cliente',
                'is_system' => false,
                'is_active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['code' => $roleData['code']],
                $roleData
            );
        }

        $this->command->info('✓ Roles creados exitosamente');
    }
}
