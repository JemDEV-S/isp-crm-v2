<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Finance\DTOs\BillingContext;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\InvoiceItemType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;
use Modules\Finance\Events\InitialInvoiceGenerated;
use Modules\Finance\Events\InvoiceGenerated;
use Modules\Finance\Events\InvoicePaymentReceived;
use Modules\Subscription\Entities\Subscription;

class InvoiceService
{
    public function generateInitialInvoice(int $subscriptionId): Invoice
    {
        $subscription = Subscription::with(['customer', 'addons'])->findOrFail($subscriptionId);

        $existing = Invoice::where('subscription_id', $subscription->id)
            ->where('type', 'initial')
            ->first();

        if ($existing) {
            return $existing->load('items');
        }

        return DB::transaction(function () use ($subscription) {
            $activationDate = $subscription->start_date ? Carbon::parse($subscription->start_date) : now();
            $prorate = $this->calculateProrate($subscription->id, $activationDate);
            $installationFee = (float) $subscription->installation_fee;
            $addons = (float) $subscription->getTotalAddonsPrice();

            $subtotal = round($installationFee + $prorate + $addons, 2);
            $tax = 0.0;
            $total = round($subtotal + $tax, 2);

            $invoice = Invoice::create([
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'type' => 'initial',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'balance_due' => $total,
                'due_date' => $subscription->getNextBillingDate()->toDateString(),
                'status' => 'issued',
                'metadata' => [
                    'activation_date' => $activationDate->toDateString(),
                    'billing_day' => $subscription->billing_day,
                ],
            ]);

            if ($installationFee > 0) {
                $invoice->items()->create([
                    'concept' => 'installation',
                    'description' => 'Cargo de instalacion',
                    'quantity' => 1,
                    'unit_price' => $installationFee,
                    'subtotal' => $installationFee,
                    'tax' => 0,
                ]);
            }

            if ($prorate > 0) {
                $invoice->items()->create([
                    'concept' => 'prorate',
                    'description' => 'Prorrateo del primer ciclo',
                    'quantity' => 1,
                    'unit_price' => $prorate,
                    'subtotal' => $prorate,
                    'tax' => 0,
                ]);
            }

            if ($addons > 0) {
                $invoice->items()->create([
                    'concept' => 'addons',
                    'description' => 'Cargos iniciales por addons',
                    'quantity' => 1,
                    'unit_price' => $addons,
                    'subtotal' => $addons,
                    'tax' => 0,
                ]);
            }

            $invoice = $invoice->fresh(['items']);

            event(new InitialInvoiceGenerated($invoice));

            return $invoice;
        });
    }

    public function calculateProrate(int $subscriptionId, Carbon $activationDate): float
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        $billingDay = max(1, min(28, (int) $subscription->billing_day));
        $periodStart = $activationDate->copy();
        $periodEnd = $activationDate->copy()->day($billingDay);

        if ($periodEnd->lessThanOrEqualTo($activationDate)) {
            $periodEnd->addMonth();
        }

        $daysInPeriod = max(1, $periodStart->daysInMonth);
        $daysToCharge = max(1, $activationDate->diffInDays($periodEnd));
        $monthlyAmount = (float) $subscription->getTotalMonthlyPrice();

        return round(($monthlyAmount / $daysInPeriod) * $daysToCharge, 2);
    }

    public function sendInvoice(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $metadata = $invoice->metadata ?? [];
        $metadata['sent_at'] = now()->toIso8601String();

        $invoice->update([
            'metadata' => $metadata,
        ]);
    }

    public function generateRecurringInvoice(BillingContext $context): Invoice
    {
        $existing = Invoice::where('subscription_id', $context->subscription->id)
            ->where('billing_period', $context->billingPeriod)
            ->where('type', InvoiceType::MONTHLY)
            ->whereNot('status', InvoiceStatus::CANCELLED)
            ->first();

        if ($existing) {
            return $existing->load('items');
        }

        return DB::transaction(function () use ($context) {
            $gracePeriod = (int) config('finance.billing.grace_period_days', 10);

            $invoice = Invoice::create([
                'customer_id' => $context->subscription->customer_id,
                'subscription_id' => $context->subscription->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'type' => InvoiceType::MONTHLY,
                'billing_period' => $context->billingPeriod,
                'period_start' => $context->periodStart,
                'period_end' => $context->periodEnd,
                'subtotal' => $context->subtotal,
                'tax' => $context->taxAmount,
                'total' => $context->total,
                'balance_due' => $context->total,
                'due_date' => $context->periodStart->copy()->addDays($gracePeriod),
                'status' => InvoiceStatus::ISSUED,
                'calculation_snapshot' => $context->toCalculationSnapshot(),
                'generation_source' => $context->generationSource,
                'issued_by_job_run_id' => $context->jobRunId,
            ]);

            // Item: servicio mensual
            $invoice->items()->create([
                'code' => 'SVC-MONTHLY',
                'type' => InvoiceItemType::SERVICE,
                'concept' => 'Servicio mensual',
                'description' => $context->subscription->plan->name ?? 'Plan de internet',
                'quantity' => 1,
                'unit_price' => $context->basePrice,
                'subtotal' => $context->basePrice,
                'tax' => 0,
                'billing_period_start' => $context->periodStart,
                'billing_period_end' => $context->periodEnd,
                'source_reference' => 'plan:' . $context->subscription->plan_id,
            ]);

            // Items: addons
            foreach ($context->activeAddons as $addon) {
                $invoice->items()->create([
                    'code' => 'ADDON-' . $addon['id'],
                    'type' => InvoiceItemType::ADDON,
                    'concept' => 'Addon: ' . $addon['name'],
                    'description' => $addon['name'],
                    'quantity' => 1,
                    'unit_price' => $addon['price'],
                    'subtotal' => $addon['price'],
                    'tax' => 0,
                    'billing_period_start' => $context->periodStart,
                    'billing_period_end' => $context->periodEnd,
                    'source_reference' => 'addon:' . $addon['id'],
                ]);
            }

            // Item: descuento (como item negativo)
            if ($context->discountAmount > 0) {
                $invoice->items()->create([
                    'code' => 'DISC-PROMO',
                    'type' => InvoiceItemType::DISCOUNT,
                    'concept' => 'Descuento promocional',
                    'description' => $context->discountPercentage . '% descuento',
                    'quantity' => 1,
                    'unit_price' => -$context->discountAmount,
                    'subtotal' => -$context->discountAmount,
                    'tax' => 0,
                    'billing_period_start' => $context->periodStart,
                    'billing_period_end' => $context->periodEnd,
                    'source_reference' => 'promotion:' . ($context->subscription->promotion_id ?? 'none'),
                ]);
            }

            // Item: impuestos
            if ($context->taxAmount > 0) {
                $invoice->items()->create([
                    'code' => 'TAX-IVA',
                    'type' => InvoiceItemType::TAX,
                    'concept' => 'Impuesto',
                    'description' => config('finance.billing.tax_name', 'IGV'),
                    'quantity' => 1,
                    'unit_price' => $context->taxAmount,
                    'subtotal' => $context->taxAmount,
                    'tax' => 0,
                    'billing_period_start' => $context->periodStart,
                    'billing_period_end' => $context->periodEnd,
                ]);
            }

            // Decrementar meses de descuento restantes
            if ($context->discountMonthsRemaining > 0) {
                $context->subscription->decrement('discount_months_remaining');
            }

            event(new InvoiceGenerated($invoice->fresh('items')));

            return $invoice->fresh('items');
        });
    }

    public function markAsPaid(int $invoiceId): Invoice
    {
        $invoice = Invoice::findOrFail($invoiceId);

        $invoice->update([
            'status' => InvoiceStatus::PAID,
            'paid_at' => now(),
            'total_paid' => $invoice->total,
            'balance_due' => 0,
        ]);

        event(new InvoicePaymentReceived($invoice->fresh()));

        return $invoice->fresh(['items']);
    }

    protected function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $last = Invoice::whereYear('created_at', $year)->orderByDesc('id')->first();
        $next = $last ? $last->id + 1 : 1;

        return 'FAC-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
