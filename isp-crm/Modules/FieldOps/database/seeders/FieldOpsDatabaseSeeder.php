<?php

namespace Modules\FieldOps\Database\Seeders;

use Illuminate\Database\Seeder;

class FieldOpsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WorkOrderTypeSeeder::class,
        ]);
    }
}
