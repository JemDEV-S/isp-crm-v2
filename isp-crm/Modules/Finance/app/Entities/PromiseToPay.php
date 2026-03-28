<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AccessControl\Entities\User;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Enums\PromiseStatus;
use Modules\Subscription\Entities\Subscription;

class PromiseToPay extends Model
{
    protected $table = 'promises_to_pay';

    protected $fillable = [
        'subscription_id',
        'customer_id',
        'invoice_id',
        'promised_amount',
        'promise_date',
        'status',
        'source_channel',
        'notes',
        'approved_by',
        'approved_at',
        'fulfilled_at',
        'broken_at',
        'max_extensions',
        'extensions_used',
        'created_by',
    ];

    protected $casts = [
        'promised_amount' => 'decimal:2',
        'promise_date' => 'date',
        'status' => PromiseStatus::class,
        'approved_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'broken_at' => 'datetime',
        'max_extensions' => 'integer',
        'extensions_used' => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === PromiseStatus::PENDING && $this->promise_date->gte(now()->startOfDay());
    }

    public function isExpired(): bool
    {
        return $this->status === PromiseStatus::PENDING && $this->promise_date->lt(now()->startOfDay());
    }

    public function canBeExtended(): bool
    {
        return $this->extensions_used < $this->max_extensions;
    }
}
