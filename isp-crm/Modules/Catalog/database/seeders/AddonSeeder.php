<?php

declare(strict_types=1);

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Addon;
use Modules\Catalog\Entities\Plan;

class AddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addons = [
            [
                'code' => 'IP-PUBLICA',
                'name' => 'IP Pública Fija',
                'description' => 'Dirección IP pública dedicada para acceso remoto',
                'price' => 30.00,
                'is_recurring' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ROUTER-PREMIUM',
                'name' => 'Router Premium',
                'description' => 'Upgrade a router de mayor capacidad con WiFi 6',
                'price' => 50.00,
                'is_recurring' => false, // Pago único
                'is_active' => true,
            ],
            [
                'code' => 'SOPORTE-PRIORITARIO',
                'name' => 'Soporte Prioritario',
                'description' => 'Atención preferencial en soporte técnico 24/7',
                'price' => 15.00,
                'is_recurring' => true,
                'is_active' => true,
            ],
            [
                'code' => 'EXTENSION-WIFI',
                'name' => 'Extensión WiFi',
                'description' => 'Repetidor WiFi para mayor cobertura en el hogar',
                'price' => 40.00,
                'is_recurring' => false, // Pago único
                'is_active' => true,
            ],
            [
                'code' => 'STREAMING-PACK',
                'name' => 'Pack Streaming',
                'description' => 'Acceso a plataformas de streaming incluido',
                'price' => 25.00,
                'is_recurring' => true,
                'is_active' => true,
            ],
            [
                'code' => 'BACKUP-NUBE',
                'name' => 'Backup en la Nube',
                'description' => '100GB de almacenamiento en la nube',
                'price' => 10.00,
                'is_recurring' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ANTIVIRUS',
                'name' => 'Antivirus Premium',
                'description' => 'Licencia de antivirus para 5 dispositivos',
                'price' => 12.00,
                'is_recurring' => true,
                'is_active' => true,
            ],
            [
                'code' => 'INSTALACION-EXPRESS',
                'name' => 'Instalación Express',
                'description' => 'Instalación garantizada en 24 horas',
                'price' => 50.00,
                'is_recurring' => false, // Pago único
                'is_active' => true,
            ],
        ];

        foreach ($addons as $addonData) {
            $addon = Addon::create([
                ...$addonData,
                'uuid' => Str::uuid()->toString(),
            ]);

            // Asociar con todos los planes activos
            $allPlans = Plan::where('is_active', true)->pluck('id');
            $addon->plans()->attach($allPlans);
        }

        // IP Pública incluida en plan empresarial
        $businessPlan = Plan::where('code', 'BUSINESS-100')->first();
        $ipPublica = Addon::where('code', 'IP-PUBLICA')->first();

        if ($businessPlan && $ipPublica) {
            $businessPlan->addons()->updateExistingPivot($ipPublica->id, ['is_included' => true]);
        }
    }
}
