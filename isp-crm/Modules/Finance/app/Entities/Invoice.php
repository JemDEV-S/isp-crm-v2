<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Enums\AgingBucket;
use Modules\Finance\Enums\GenerationSource;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;
use Modules\Subscription\Entities\Subscription;

class Invoice extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\InvoiceFactory::new();
    }
    protected $fillable = [
        'customer_id',
        'subscription_id',
        'invoice_number',
        'type',
        'billing_period',
        'period_start',
        'period_end',
        'subtotal',
        'tax',
        'total',
        'total_paid',
        'balance_due',
        'due_date',
        'paid_at',
        'status',
        'metadata',
        'calculation_snapshot',
        'generation_source',
        'external_tax_status',
        'issued_by_job_run_id',
        'days_overdue',
        'aging_bucket',
        'last_dunning_stage_id',
        'dunning_paused',
        'dunning_pause_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
        'metadata' => 'array',
        'calculation_snapshot' => 'array',
        'type' => InvoiceType::class,
        'status' => InvoiceStatus::class,
        'generation_source' => GenerationSource::class,
        'days_overdue' => 'integer',
        'aging_bucket' => AgingBucket::class,
        'dunning_paused' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function billingJobRun(): BelongsTo
    {
        return $this->belongsTo(BillingJobRun::class, 'issued_by_job_run_id');
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null || $this->status === InvoiceStatus::PAID;
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            PaymentAllocation::class,
            'invoice_id',
            'id',
            'id',
            'payment_id'
        );
    }

    public function recalculateTotals(): void
    {
        $this->total_paid = (float) $this->allocations()->sum('amount');
        $this->balance_due = round((float) $this->total - $this->total_paid, 2);
        $this->save();
    }

    public function dunningExecutions(): HasMany
    {
        return $this->hasMany(DunningExecution::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(InvoiceDispute::class);
    }

    public function lastDunningStage(): BelongsTo
    {
        return $this->belongsTo(DunningStage::class, 'last_dunning_stage_id');
    }

    public function scopeForPeriod($query, string $billingPeriod)
    {
        return $query->where('billing_period', $billingPeriod);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::CANCELLED]);
    }

    public function scopeAgingBucket($query, AgingBucket $bucket)
    {
        return $query->where('aging_bucket', $bucket);
    }

    public function scopeDunningEligible($query)
    {
        return $query->overdue()
            ->where('dunning_paused', false)
            ->whereDoesntHave('disputes', function ($q) {
                $q->whereIn('status', ['open', 'under_review']);
            });
    }
}
