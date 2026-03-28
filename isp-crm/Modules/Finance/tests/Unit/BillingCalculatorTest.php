<?php

declare(strict_types=1);

namespace Modules\Finance\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Entities\Addon;
use Modules\Catalog\Entities\Plan;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Services\BillingCalculator;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Enums\SubscriptionStatus;
use Tests\TestCase;

class BillingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected BillingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new BillingCalculator();
    }

    /** @test */
    public function it_calculates_base_price_correctly()
    {
        $subscription = $this->createSubscription([
            'monthly_price' => 50.00,
            'billing_day' => 15,
            'discount_percentage' => 0,
            'discount_months_remaining' => 0,
        ]);

        $context = $this->calculator->buildContext($subscription, '2026-03');

        $this->assertEquals(50.00, $context->basePrice);
        $this->assertEquals(50.00, $context->effectivePrice);
        $this->assertEquals(0.0, $context->discountAmount);
        $this->assertEquals(50.00, $context->subtotal);
        $this->assertEquals(50.00, $context->total);
    }

    /** @test */
    public function it_applies_percentage_discount()
    {
        $subscription = $this->createSubscription([
            'monthly_price' => 100.00,
            'billing_day' => 15,
            'discount_percentage' => 20,
            'discount_months_remaining' => 3,
        ]);

        $context = $this->calculator->buildContext($subscription, '2026-03');

        $this->assertEquals(100.00, $context->basePrice);
        $this->assertEquals(80.00, $context->effectivePrice);
        $this->assertEquals(20.00, $context->discountAmount);
        $this->assertEquals(20.0, $context->discountPercentage);
        $this->assertEquals(3, $context->discountMonthsRemaining);
    }

    /** @test */
    public function it_does_not_apply_discount_when_months_remaining_is_zero()
    {
        $subscription = $this->createSubscription([
            'monthly_price' => 100.00,
            'billing_day' => 15,
            'discount_percentage' => 20,
            'discount_months_remaining' => 0,
        ]);

        $context = $this->calculator->buildContext($subscription, '2026-03');

        $this->assertEquals(100.00, $context->effectivePrice);
        $this->assertEquals(0.0, $context->discountAmount);
    }

    /** @test */
    public function it_includes_active_addons()
    {
        $subscription = $this->createSubscription([
            'monthly_price' => 50.00,
            'billing_day' => 15,
        ]);

        $addon = Addon::factory()->create(['name' => 'IP Publica']);
        $subscription->addons()->attach($addon->id, [
            'price' => 10.00,
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);

        $context = $this->calculator->buildContext($subscription, '2026-03');

        $this->assertCount(1, $context->activeAddons);
        $this->assertEquals(10.00, $context->addonsTotal);
        $this->assertEquals(60.00, $context->subtotal);
    }

    /** @test */
    public function it_excludes_expired_addons()
    {
        $subscription = $this->createSubscription([
            'monthly_price' => 50.00,
            'billing_day' => 15,
        ]);

        $addon = Addon::factory()->create(['name' => 'IP Publica']);
        $subscription->addons()->attach($addon->id, [
            'price' => 10.00,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);

        $context = $this->calculator->buildContext($subscription, '2026-03');

        $this->assertCount(0, $context->activeAddons);
        $this->assertEquals(0.0, $context->addonsTotal);
    }

    /** @test */
    public function it_calculates_tax_when_enabled()
    {
        config(['finance.billing.tax_enabled' => true, 'finance.billing.tax_rate' => 0.18]);

        $subscription = $this->createSubscription([
            'monthly_price' => 100.00,
            'billing_day' => 15,
        ]);

        $context = $this->calculator->buildContext($subscription, '2026-03');

        $this->assertEquals(18.00, $context->taxAmount);
        $this->assertEquals(118.00, $context->total);
    }

    /** @test */
    public function it_calculates_period_dates_correctly()
    {
        $subscription = $this->createSubscription([
            'monthly_price' => 50.00,
            'billing_day' => 15,
        ]);

        $context = $this->calculator->buildContext($subscription, '2026-03');

        $this->assertEquals('2026-03-15', $context->periodStart->toDateString());
        $this->assertEquals('2026-04-14', $context->periodEnd->toDateString());
    }

    /** @test */
    public function it_handles_short_month_billing_day()
    {
        $subscription = $this->createSubscription([
            'monthly_price' => 50.00,
            'billing_day' => 31,
        ]);

        // February only has 28 days
        $context = $this->calculator->buildContext($subscription, '2026-02');

        $this->assertEquals('2026-02-28', $context->periodStart->toDateString());
    }

    protected function createSubscription(array $overrides = []): Subscription
    {
        $customer = Customer::factory()->create();
        $plan = Plan::factory()->create();

        return Subscription::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'start_date' => '2026-01-01',
            'billing_day' => 15,
            'monthly_price' => 50.00,
            'discount_percentage' => 0,
            'discount_months_remaining' => 0,
        ], $overrides));
    }
}
