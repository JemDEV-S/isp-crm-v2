<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'subscription_id' => null,
            'invoice_number' => 'FAC-' . now()->year . '-' . str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'type' => InvoiceType::MONTHLY,
            'billing_period' => now()->format('Y-m'),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'subtotal' => $this->faker->randomFloat(2, 10, 500),
            'tax' => 0,
            'total' => $this->faker->randomFloat(2, 10, 500),
            'due_date' => now()->addDays(10),
            'status' => InvoiceStatus::ISSUED,
            'generation_source' => 'scheduled',
        ];
    }
}
