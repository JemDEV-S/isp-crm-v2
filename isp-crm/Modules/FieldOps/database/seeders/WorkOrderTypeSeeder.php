<?php

declare(strict_types=1);

namespace Modules\FieldOps\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\FieldOps\app\Enums\WorkOrderType as WorkOrderTypeEnum;
use Modules\FieldOps\app\Models\WorkOrderType;

class WorkOrderTypeSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            WorkOrderTypeEnum::INSTALLATION->value => [
                'name' => WorkOrderTypeEnum::INSTALLATION->label(),
                'workflow_code' => 'installation',
                'default_duration_minutes' => 180,
                'requires_materials' => true,
            ],
            WorkOrderTypeEnum::REPAIR->value => [
                'name' => WorkOrderTypeEnum::REPAIR->label(),
                'workflow_code' => null,
                'default_duration_minutes' => 120,
                'requires_materials' => true,
            ],
            WorkOrderTypeEnum::RELOCATION->value => [
                'name' => WorkOrderTypeEnum::RELOCATION->label(),
                'workflow_code' => null,
                'default_duration_minutes' => 180,
                'requires_materials' => true,
            ],
            WorkOrderTypeEnum::UPGRADE->value => [
                'name' => WorkOrderTypeEnum::UPGRADE->label(),
                'workflow_code' => null,
                'default_duration_minutes' => 120,
                'requires_materials' => true,
            ],
            WorkOrderTypeEnum::DOWNGRADE->value => [
                'name' => WorkOrderTypeEnum::DOWNGRADE->label(),
                'workflow_code' => null,
                'default_duration_minutes' => 90,
                'requires_materials' => false,
            ],
            WorkOrderTypeEnum::EQUIPMENT_CHANGE->value => [
                'name' => WorkOrderTypeEnum::EQUIPMENT_CHANGE->label(),
                'workflow_code' => null,
                'default_duration_minutes' => 120,
                'requires_materials' => true,
            ],
            WorkOrderTypeEnum::CANCELLATION->value => [
                'name' => WorkOrderTypeEnum::CANCELLATION->label(),
                'workflow_code' => null,
                'default_duration_minutes' => 90,
                'requires_materials' => false,
            ],
            WorkOrderTypeEnum::PREVENTIVE->value => [
                'name' => WorkOrderTypeEnum::PREVENTIVE->label(),
                'workflow_code' => null,
                'default_duration_minutes' => 60,
                'requires_materials' => false,
            ],
        ];

        foreach ($definitions as $code => $definition) {
            WorkOrderType::updateOrCreate(
                ['code' => $code],
                [
                    ...$definition,
                    'is_active' => true,
                ]
            );
        }
    }
}
