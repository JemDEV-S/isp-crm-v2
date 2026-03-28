<?php

declare(strict_types=1);

namespace Modules\Finance\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Entities\Plan;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Entities\BillingIncident;
use Modules\Finance\Entities\BillingJobRun;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;
use Modules\Finance\Events\InvoiceBatchCompleted;
use Modules\Finance\Events\InvoiceGenerated;
use Modules\Finance\Events\InvoiceGenerationStarted;
use Modules\Finance\Services\RecurringBillingService;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Enums\SubscriptionStatus;
use Tests\TestCase;

class RecurringBillingTest extends TestCase
{
    use RefreshDatabase;

    protected RecurringBillingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecurringBillingService::class);
    }

    /** @test */
    public function run_billing_cycle_generates_invoices_for_eligible_subscriptions()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));
        Event::fake();

        $customer = Customer::factory()->create();
        $plan = Plan::factory()->create();

        // 3 suscripciones activas con billing_day = 15
        $activeSubscriptions = [];
        for ($i = 0; $i < 3; $i++) {
            $activeSubscriptions[] = Subscription::factory()->create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::ACTIVE,
                'billing_day' => 15,
                'start_date' => '2026-01-01',
                'monthly_price' => 50.00,
            ]);
        }

        // 1 suscripcion suspendida (no deberia facturarse)
        Subscription::factory()->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::SUSPENDED,
            'billing_day' => 15,
            'start_date' => '2026-01-01',
            'monthly_price' => 50.00,
        ]);

        // 1 suscripcion activa ya facturada (no deberia duplicarse)
        $alreadyBilled = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_day' => 15,
            'start_date' => '2026-01-01',
            'monthly_price' => 50.00,
        ]);
        Invoice::factory()->create([
            'subscription_id' => $alreadyBilled->id,
            'customer_id' => $customer->id,
            'billing_period' => '2026-03',
            'type' => InvoiceType::MONTHLY,
            'status' => InvoiceStatus::ISSUED,
        ]);

        $jobRun = $this->service->runBillingCycle('2026-03', 'artisan');

        // 3 facturas generadas (las activas sin factura previa)
        $this->assertEquals(3, $jobRun->total_invoiced);
        $this->assertEquals(3, $jobRun->total_processed);
        $this->assertStringContainsString('completed', $jobRun->status);

        // La ya facturada no se incluyo en el query (whereDoesntHave)
        $totalInvoices = Invoice::where('billing_period', '2026-03')
            ->where('type', InvoiceType::MONTHLY)
            ->count();
        $this->assertEquals(4, $totalInvoices); // 3 nuevas + 1 preexistente

        Event::assertDispatched(InvoiceGenerationStarted::class);
        Event::assertDispatched(InvoiceGenerated::class, 3);
        Event::assertDispatched(InvoiceBatchCompleted::class);

        Carbon::setTestNow();
    }

    /** @test */
    public function idempotent_billing_does_not_duplicate()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));
        Event::fake();

        $subscription = $this->createSubscription([
            'billing_day' => 15,
            'monthly_price' => 50.00,
        ]);

        // Primera corrida
        $jobRun1 = $this->service->runBillingCycle('2026-03', 'artisan');
        $this->assertEquals(1, $jobRun1->total_invoiced);

        // Segunda corrida - no deberia duplicar
        $jobRun2 = $this->service->runBillingCycle('2026-03', 'artisan');
        $this->assertEquals(0, $jobRun2->total_processed); // No hay elegibles

        $totalInvoices = Invoice::where('subscription_id', $subscription->id)
            ->where('billing_period', '2026-03')
            ->where('type', InvoiceType::MONTHLY)
            ->count();
        $this->assertEquals(1, $totalInvoices);

        Carbon::setTestNow();
    }

    /** @test */
    public function discount_months_decrement_on_billing()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));
        Event::fake();

        $subscription = $this->createSubscription([
            'billing_day' => 15,
            'monthly_price' => 100.00,
            'discount_percentage' => 25,
            'discount_months_remaining' => 3,
        ]);

        $jobRun = $this->service->runBillingCycle('2026-03', 'artisan');

        $this->assertEquals(1, $jobRun->total_invoiced);
        $this->assertEquals(2, $subscription->fresh()->discount_months_remaining);

        // Verificar item de descuento
        $invoice = Invoice::where('subscription_id', $subscription->id)
            ->where('billing_period', '2026-03')
            ->first();

        $discountItem = $invoice->items()->where('type', 'discount')->first();
        $this->assertNotNull($discountItem);
        $this->assertEquals(-25.00, (float) $discountItem->unit_price);

        Carbon::setTestNow();
    }

    /** @test */
    public function billing_handles_subscription_failure_gracefully()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));
        Event::fake();

        // Suscripcion valida
        $validSubscription = $this->createSubscription([
            'billing_day' => 15,
            'monthly_price' => 50.00,
        ]);

        // Suscripcion con datos incompletos (sin plan, forzara error)
        $badSubscription = $this->createSubscription([
            'billing_day' => 15,
            'monthly_price' => 50.00,
            'plan_id' => 99999, // plan inexistente
        ]);

        $jobRun = $this->service->runBillingCycle('2026-03', 'artisan');

        // La valida se facturo, la mala genero incidente
        $this->assertGreaterThanOrEqual(1, $jobRun->total_invoiced);
        $this->assertGreaterThanOrEqual(0, $jobRun->total_failed);

        // El job no se detuvo
        $this->assertStringContainsString('completed', $jobRun->status);

        Carbon::setTestNow();
    }

    /** @test */
    public function billing_job_run_records_are_created_with_correct_counters()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));
        Event::fake();

        $this->createSubscription(['billing_day' => 15, 'monthly_price' => 50.00]);
        $this->createSubscription(['billing_day' => 15, 'monthly_price' => 75.00]);

        $jobRun = $this->service->runBillingCycle('2026-03', 'artisan', 1);

        $this->assertInstanceOf(BillingJobRun::class, $jobRun);
        $this->assertEquals('2026-03', $jobRun->billing_period);
        $this->assertEquals('artisan', $jobRun->triggered_by);
        $this->assertEquals(2, $jobRun->total_invoiced);
        $this->assertEquals(2, $jobRun->total_processed);
        $this->assertNotNull($jobRun->completed_at);

        Carbon::setTestNow();
    }

    /** @test */
    public function artisan_command_generates_invoices_sync()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));
        Event::fake();

        $this->createSubscription(['billing_day' => 15, 'monthly_price' => 50.00]);

        $this->artisan('finance:generate-invoices', ['--sync' => true, '--period' => '2026-03'])
            ->assertSuccessful();

        $this->assertEquals(1, Invoice::where('billing_period', '2026-03')->count());

        Carbon::setTestNow();
    }

    /** @test */
    public function artisan_command_generates_single_subscription_invoice()
    {
        Event::fake();

        $subscription = $this->createSubscription([
            'billing_day' => 15,
            'monthly_price' => 50.00,
        ]);

        $this->artisan('finance:generate-invoices', [
            '--subscription' => $subscription->id,
            '--period' => '2026-03',
        ])->assertSuccessful();

        $this->assertEquals(1, Invoice::where('subscription_id', $subscription->id)->count());
    }

    protected function createSubscription(array $overrides = []): Subscription
    {
        $customer = $overrides['customer_id'] ?? Customer::factory()->create()->id;
        $plan = $overrides['plan_id'] ?? Plan::factory()->create()->id;

        return Subscription::factory()->create(array_merge([
            'customer_id' => $customer,
            'plan_id' => $plan,
            'status' => SubscriptionStatus::ACTIVE,
            'start_date' => '2026-01-01',
            'billing_day' => 15,
            'monthly_price' => 50.00,
            'discount_percentage' => 0,
            'discount_months_remaining' => 0,
        ], $overrides));
    }
}
