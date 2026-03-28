<?php

namespace Tests\Unit\Subscription;

use Illuminate\Support\Collection;
use Modules\Catalog\Entities\Addon;
use Modules\Catalog\Entities\Plan;
use Modules\Catalog\Entities\PlanParameter;
use Modules\Catalog\Entities\Promotion;
use Modules\Catalog\Enums\DiscountType;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Services\SubscriptionContractService;
use PHPUnit\Framework\TestCase;

class SubscriptionContractServiceTest extends TestCase
{
    public function test_it_builds_a_commercial_snapshot(): void
    {
        $service = new SubscriptionContractService();

        $plan = new Plan([
            'id' => 10,
            'name' => 'Plan 100MB',
        ]);
        $plan->setRelation('parameters', new Collection([
            new PlanParameter([
                'key' => 'vlan_id',
                'value' => '200',
                'display_name' => 'VLAN ID',
            ]),
        ]));

        $promotion = new Promotion([
            'id' => 5,
            'name' => 'Promo Bienvenida',
            'discount_type' => DiscountType::PERCENTAGE,
            'discount_value' => 50,
        ]);

        $addon = new Addon([
            'id' => 8,
            'name' => 'IP Estatica',
        ]);
        $addon->setRelation('pivot', (object) ['price' => 10]);

        $subscription = new Subscription([
            'monthly_price' => 60,
            'installation_fee' => 80,
            'billing_day' => 15,
            'contracted_months' => 12,
            'discount_percentage' => 50,
            'discount_months_remaining' => 1,
        ]);
        $subscription->setRelation('plan', $plan);
        $subscription->setRelation('promotion', $promotion);
        $subscription->setRelation('addons', new Collection([$addon]));
        $subscription->billing_cycle = \Modules\Subscription\Enums\BillingCycle::MONTHLY;

        $snapshot = $service->freezeCommercialSnapshot($subscription);

        $this->assertSame('Plan 100MB', $snapshot['plan']['name']);
        $this->assertSame('vlan_id', $snapshot['plan']['parameters'][0]['key']);
        $this->assertSame('Promo Bienvenida', $snapshot['promotion']['name']);
        $this->assertSame('IP Estatica', $snapshot['addons'][0]['name']);
        $this->assertArrayHasKey('estimated_first_invoice', $snapshot);
        $this->assertTrue($snapshot['future_contract_module_ready']);
    }
}
