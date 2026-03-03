<?php

declare(strict_types=1);

namespace Modules\AccessControl\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AccessControl\Entities\Zone;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            [
                'code' => 'MAIN',
                'name' => 'Zona Principal',
                'parent_id' => null,
                'description' => 'Zona principal del sistema',
                'is_active' => true,
            ],
        ];

        foreach ($zones as $zoneData) {
            Zone::firstOrCreate(
                ['code' => $zoneData['code']],
                $zoneData
            );
        }

        $this->command->info('✓ Zonas creadas exitosamente');
    }
}
