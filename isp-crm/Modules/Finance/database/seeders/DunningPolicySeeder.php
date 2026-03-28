<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Entities\DunningPolicy;
use Modules\Finance\Entities\DunningStage;

class DunningPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policy = DunningPolicy::updateOrCreate(
            ['code' => 'standard_residential'],
            [
                'name' => 'Estándar Residencial',
                'description' => 'Política de cobranza estándar para clientes residenciales',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $stages = [
            [
                'stage_order' => 1,
                'name' => 'Primer recordatorio',
                'code' => 'reminder_1',
                'action_type' => 'reminder',
                'min_days_overdue' => 0,
                'max_days_overdue' => 2,
                'channels' => ['email', 'sms'],
                'template_code' => 'dunning_reminder_1',
                'auto_execute' => true,
                'requires_approval' => false,
            ],
            [
                'stage_order' => 2,
                'name' => 'Segundo recordatorio',
                'code' => 'reminder_2',
                'action_type' => 'reminder',
                'min_days_overdue' => 3,
                'max_days_overdue' => 6,
                'channels' => ['email', 'sms', 'whatsapp'],
                'template_code' => 'dunning_reminder_2',
                'auto_execute' => true,
                'requires_approval' => false,
            ],
            [
                'stage_order' => 3,
                'name' => 'Aviso de corte inminente',
                'code' => 'suspension_warning',
                'action_type' => 'warning',
                'min_days_overdue' => 7,
                'max_days_overdue' => 9,
                'channels' => ['email', 'sms', 'whatsapp'],
                'template_code' => 'dunning_suspension_warning',
                'auto_execute' => true,
                'requires_approval' => false,
            ],
            [
                'stage_order' => 4,
                'name' => 'Corte automático',
                'code' => 'service_cut',
                'action_type' => 'suspension',
                'min_days_overdue' => 10,
                'max_days_overdue' => 29,
                'channels' => ['email', 'sms'],
                'template_code' => 'dunning_service_cut',
                'auto_execute' => true,
                'requires_approval' => false,
            ],
            [
                'stage_order' => 5,
                'name' => 'Aviso final pre-cancelación',
                'code' => 'pre_termination',
                'action_type' => 'pre_termination',
                'min_days_overdue' => 30,
                'max_days_overdue' => 59,
                'channels' => ['email', 'sms', 'whatsapp'],
                'template_code' => 'dunning_pre_termination',
                'auto_execute' => true,
                'requires_approval' => false,
            ],
            [
                'stage_order' => 6,
                'name' => 'Envío a cobranza externa',
                'code' => 'external_collection',
                'action_type' => 'external_collection',
                'min_days_overdue' => 60,
                'max_days_overdue' => 9999,
                'channels' => ['email'],
                'template_code' => 'dunning_external_collection',
                'auto_execute' => true,
                'requires_approval' => true,
            ],
        ];

        foreach ($stages as $stageData) {
            DunningStage::updateOrCreate(
                [
                    'dunning_policy_id' => $policy->id,
                    'stage_order' => $stageData['stage_order'],
                ],
                array_merge($stageData, ['dunning_policy_id' => $policy->id])
            );
        }
    }
}
