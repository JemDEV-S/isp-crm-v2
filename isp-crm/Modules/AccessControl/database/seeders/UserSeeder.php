<?php

declare(strict_types=1);

namespace Modules\AccessControl\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\AccessControl\Entities\Role;
use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Entities\Zone;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener zona principal
        $mainZone = Zone::where('code', 'MAIN')->first();

        // Crear usuario superadmin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@noretel.com'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Super Administrador',
                'email' => 'admin@noretel.com',
                'password' => Hash::make('password'),
                'phone' => null,
                'is_active' => true,
                'zone_id' => $mainZone?->id,
                'email_verified_at' => now(),
            ]
        );

        // Asignar rol superadmin
        $superAdminRole = Role::where('code', 'superadmin')->first();
        if ($superAdminRole && !$superAdmin->hasRole('superadmin')) {
            $superAdmin->assignRoles([$superAdminRole->id]);
        }

        $this->command->info('✓ Usuario superadmin creado exitosamente');
        $this->command->warn('  Email: admin@noretel.com');
        $this->command->warn('  Password: password');
        $this->command->error('  ⚠ CAMBIAR LA CONTRASEÑA EN PRODUCCIÓN');
    }
}
