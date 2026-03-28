<?php

declare(strict_types=1);

namespace Modules\Finance\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Entities\Plan;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;
use Modules\Finance\Services\BillingCalculator;
use Modules\Finance\Services\InvoiceService;
use Modules\Finance\Services\RecurringBillingService;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Enums\SubscriptionStatus;
use Tests\TestCase;

class RecurringBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecurringBillingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecurringBillingService::class);
    }

    /** @test */
    public function is_eligible_returns_true_for_active_subscription_with_matching_billing_day()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));

        $subscription = $this->createSubscription([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_day' => 15,
            'start_date' => '2026-01-01',
        ]);

        $this->assertTrue($this->service->isEligible($subscription, '2026-03'));

        Carbon::setTestNow();
    }

    /** @test */
    public function is_eligible_returns_false_for_suspended_subscription()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));

        $subscription = $this->createSubscription([
            'status' => SubscriptionStatus::SUSPENDED,
            'billing_day' => 15,
            'start_date' => '2026-01-01',
        ]);

        $this->assertFalse($this->service->isEligible($subscription, '2026-03'));

        Carbon::setTestNow();
    }

    /** @test */
    public function is_eligible_returns_false_if_invoice_already_exists_for_period()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));

        $subscription = $this->createSubscription([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_day' => 15,
            'start_date' => '2026-01-01',
        ]);

        Invoice::factory()->create([
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer_id,
            'billing_period' => '2026-03',
            'type' => InvoiceType::MONTHLY,
            'status' => InvoiceStatus::ISSUED,
        ]);

        $this->assertFalse($this->service->isEligible($subscription, '2026-03'));

        Carbon::setTestNow();
    }

    /** @test */
    public function is_eligible_returns_true_if_previous_invoice_was_cancelled()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));

        $subscription = $this->createSubscription([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_day' => 15,
            'start_date' => '2026-01-01',
        ]);

        Invoice::factory()->create([
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer_id,
            'billing_period' => '2026-03',
            'type' => InvoiceType::MONTHLY,
            'status' => InvoiceStatus::CANCELLED,
        ]);

        $this->assertTrue($this->service->isEligible($subscription, '2026-03'));

        Carbon::setTestNow();
    }

    /** @test */
    public function is_eligible_returns_false_if_start_date_is_after_period()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));

        $subscription = $this->createSubscription([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_day' => 15,
            'start_date' => '2026-04-01',
        ]);

        $this->assertFalse($this->service->isEligible($subscription, '2026-03'));

        Carbon::setTestNow();
    }

    /** @test */
    public function bill_subscription_is_idempotent()
    {
        $subscription = $this->createSubscription([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_day' => 15,
            'start_date' => '2026-01-01',
            'monthly_price' => 50.00,
        ]);

        $invoice1 = $this->service->billSubscription($subscription, '2026-03', 'manual');
        $invoice2 = $this->service->billSubscription($subscription, '2026-03', 'manual');

        $this->assertEquals($invoice1->id, $invoice2->id);
        $this->assertEquals(1, Invoice::where('subscription_id', $subscription->id)->where('billing_period', '2026-03')->count());
    }

    /** @test */
    public function bill_subscription_decrements_discount_months()
    {
        $subscription = $this->createSubscription([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_day' => 15,
            'start_date' => '2026-01-01',
            'monthly_price' => 100.00,
            'discount_percentage' => 20,
            'discount_months_remaining' => 3,
        ]);

        $this->service->billSubscription($subscription, '2026-03', 'manual');

        $this->assertEquals(2, $subscription->fresh()->discount_months_remaining);
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
