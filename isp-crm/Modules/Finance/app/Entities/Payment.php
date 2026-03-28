<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AccessControl\Entities\User;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Enums\PaymentChannel;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Enums\ReconciliationStatus;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'customer_id',
        'amount',
        'currency',
        'method',
        'channel',
        'status',
        'reference',
        'external_id',
        'idempotency_key',
        'gateway_response',
        'received_at',
        'validated_at',
        'reconciliation_status',
        'notes',
        'processed_by',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'method' => PaymentMethod::class,
        'channel' => PaymentChannel::class,
        'status' => PaymentStatus::class,
        'reconciliation_status' => ReconciliationStatus::class,
        'gateway_response' => 'array',
        'received_at' => 'datetime',
        'validated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    public function getRemainingAmount(): float
    {
        $allocated = (float) $this->allocations()->sum('amount');

        return round((float) $this->amount - $allocated, 2);
    }

    public function isFullyAllocated(): bool
    {
        return $this->getRemainingAmount() <= 0;
    }
}
