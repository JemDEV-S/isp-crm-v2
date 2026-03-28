<?php

namespace Modules\Subscription\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Plan;
use Modules\Crm\Entities\Customer;
use Modules\Subscription\Enums\BillingAdjustmentType;
use Modules\Subscription\Enums\EffectiveMode;
use Modules\Subscription\Enums\PlanChangeStatus;
use Modules\Subscription\Enums\PlanChangeType;

class PlanChangeRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'subscription_id',
        'customer_id',
        'old_plan_id',
        'new_plan_id',
        'change_type',
        'effective_mode',
        'effective_at',
        'scheduled_for',
        'status',
        'old_plan_snapshot',
        'new_plan_snapshot',
        'old_monthly_price',
        'new_monthly_price',
        'prorate_credit',
        'prorate_debit',
        'net_difference',
        'billing_adjustment_type',
        'feasibility_checked',
        'feasibility_result',
        'requires_approval',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'provision_status',
        'provision_result',
        'notes',
        'requested_by',
        'executed_at',
    ];

    protected $casts = [
        'change_type' => PlanChangeType::class,
        'effective_mode' => EffectiveMode::class,
        'status' => PlanChangeStatus::class,
        'billing_adjustment_type' => BillingAdjustmentType::class,
        'old_plan_snapshot' => 'array',
        'new_plan_snapshot' => 'array',
        'feasibility_result' => 'array',
        'provision_result' => 'array',
        'old_monthly_price' => 'decimal:2',
        'new_monthly_price' => 'decimal:2',
        'prorate_credit' => 'decimal:2',
        'prorate_debit' => 'decimal:2',
        'net_difference' => 'decimal:2',
        'feasibility_checked' => 'boolean',
        'requires_approval' => 'boolean',
        'effective_at' => 'datetime',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
        'scheduled_for' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relaciones ──────────────────────────────────────────────

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function oldPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'old_plan_id');
    }

    public function newPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'new_plan_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    // ── Métodos de estado ───────────────────────────────────────

    public function isUpgrade(): bool
    {
        return $this->change_type === PlanChangeType::UPGRADE;
    }

    public function isDowngrade(): bool
    {
        return $this->change_type === PlanChangeType::DOWNGRADE;
    }

    public function isPending(): bool
    {
        return $this->status === PlanChangeStatus::PENDING;
    }

    public function canBeExecuted(): bool
    {
        return in_array($this->status, [PlanChangeStatus::APPROVED, PlanChangeStatus::PENDING])
            && $this->executed_at === null;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [PlanChangeStatus::PENDING, PlanChangeStatus::APPROVED]);
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', PlanChangeStatus::PENDING);
    }

    public function scopeScheduledFor($query, Carbon $date)
    {
        return $query->where('status', PlanChangeStatus::APPROVED)
            ->whereIn('effective_mode', [EffectiveMode::NEXT_CYCLE, EffectiveMode::SCHEDULED])
            ->where('scheduled_for', '<=', $date->toDateString());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            PlanChangeStatus::PENDING,
            PlanChangeStatus::APPROVED,
            PlanChangeStatus::EXECUTING,
        ]);
    }
}
