<?php

declare(strict_types=1);

namespace Modules\Subscription\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\AccessControl\Entities\User;
use Modules\Catalog\Entities\Addon;
use Modules\Catalog\Entities\Plan;
use Modules\Catalog\Entities\Promotion;
use Modules\Crm\Entities\Address;
use Modules\Crm\Entities\Customer;
use Modules\Subscription\Enums\BillingCycle;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Workflow\Traits\HasWorkflow;

class Subscription extends Model
{
    use SoftDeletes, HasWorkflow;

    protected $fillable = [
        'uuid',
        'code',
        'customer_id',
        'plan_id',
        'address_id',
        'status',
        'billing_day',
        'billing_cycle',
        'start_date',
        'end_date',
        'contracted_months',
        'monthly_price',
        'installation_fee',
        'discount_percentage',
        'discount_months_remaining',
        'promotion_id',
        'commercial_snapshot',
        'terms_accepted_at',
        'acceptance_method',
        'acceptance_ip',
        'acceptance_user_agent',
        'notes',
        'has_pending_plan_change',
        'last_plan_change_at',
        'minimum_stay_until',
        'created_by',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'billing_cycle' => BillingCycle::class,
        'billing_day' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'contracted_months' => 'integer',
        'monthly_price' => 'decimal:2',
        'installation_fee' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_months_remaining' => 'integer',
        'commercial_snapshot' => 'array',
        'terms_accepted_at' => 'datetime',
        'has_pending_plan_change' => 'boolean',
        'last_plan_change_at' => 'datetime',
        'minimum_stay_until' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Subscription $subscription) {
            if (empty($subscription->uuid)) {
                $subscription->uuid = (string) Str::uuid();
            }
            if (empty($subscription->code)) {
                $subscription->code = self::generateCode();
            }
            if (empty($subscription->created_by) && auth()->check()) {
                $subscription->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $lastSubscription = self::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        $nextNumber = $lastSubscription ? $lastSubscription->id + 1 : 1;
        return 'SUB-' . $year . '-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serviceInstance(): HasOne
    {
        return $this->hasOne(ServiceInstance::class);
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'subscription_addons')
            ->withPivot(['price', 'start_date', 'end_date'])
            ->withTimestamps();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(SubscriptionStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SubscriptionNote::class)->orderBy('created_at', 'desc');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SubscriptionDocument::class)->orderBy('created_at', 'desc');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Entities\Invoice::class)->orderBy('created_at', 'desc');
    }

    public function promisesToPay(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Entities\PromiseToPay::class);
    }

    public function collectionCases(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Entities\CollectionCase::class);
    }

    public function planChangeRequests(): HasMany
    {
        return $this->hasMany(PlanChangeRequest::class)->orderBy('created_at', 'desc');
    }

    public function activePlanChangeRequest(): HasOne
    {
        return $this->hasOne(PlanChangeRequest::class)
            ->whereIn('status', ['pending', 'approved', 'executing'])
            ->latest();
    }

    public function hasActivePromise(): bool
    {
        return $this->promisesToPay()
            ->where('status', 'pending')
            ->where('promise_date', '>=', now()->startOfDay())
            ->exists();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isSuspended(): bool
    {
        return $this->status->isSuspended();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted_at !== null;
    }

    public function canBeSuspended(): bool
    {
        return $this->status->canBeSuspended();
    }

    public function canBeReactivated(): bool
    {
        return $this->status->canBeReactivated();
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    public function hasPendingPlanChange(): bool
    {
        return $this->has_pending_plan_change
            || $this->activePlanChangeRequest()->exists();
    }

    public function isWithinMinimumStay(): bool
    {
        return $this->minimum_stay_until !== null
            && $this->minimum_stay_until->isFuture();
    }

    public function canChangePlan(): bool
    {
        return $this->isActive()
            && ! $this->hasPendingPlanChange();
    }

    public function getEffectivePrice(): float
    {
        $price = $this->monthly_price;

        if ($this->discount_months_remaining > 0 && $this->discount_percentage > 0) {
            $discount = $price * ($this->discount_percentage / 100);
            $price -= $discount;
        }

        return round($price, 2);
    }

    public function getTotalAddonsPrice(): float
    {
        return (float) $this->addons->sum('pivot.price');
    }

    public function getTotalMonthlyPrice(): float
    {
        return $this->getEffectivePrice() + $this->getTotalAddonsPrice();
    }

    public function getNextBillingDate(): Carbon
    {
        $today = Carbon::today();
        $billingDate = Carbon::create($today->year, $today->month, $this->billing_day);

        if ($billingDate->isPast()) {
            $billingDate->addMonth();
        }

        return $billingDate;
    }

    public function getDaysUntilBilling(): int
    {
        return Carbon::today()->diffInDays($this->getNextBillingDate());
    }

    public function scopeStatus($query, SubscriptionStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    public function scopeSuspended($query)
    {
        return $query->whereIn('status', [
            SubscriptionStatus::SUSPENDED,
            SubscriptionStatus::SUSPENDED_VOLUNTARY,
        ]);
    }

    public function scopeBillingDay($query, int $day)
    {
        return $query->where('billing_day', $day);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForPlan($query, int $planId)
    {
        return $query->where('plan_id', $planId);
    }

    public function scopeBillableToday($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE)
            ->where('billing_day', now()->day);
    }
}
