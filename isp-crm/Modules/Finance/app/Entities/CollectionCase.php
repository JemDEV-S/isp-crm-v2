<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AccessControl\Entities\User;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Enums\CollectionCaseStatus;
use Modules\Subscription\Entities\Subscription;

class CollectionCase extends Model
{
    protected $fillable = [
        'customer_id',
        'subscription_id',
        'total_debt',
        'status',
        'priority',
        'assigned_to',
        'external_agency',
        'sent_to_external_at',
        'closed_at',
        'close_reason',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'total_debt' => 'decimal:2',
        'status' => CollectionCaseStatus::class,
        'metadata' => 'array',
        'sent_to_external_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [CollectionCaseStatus::OPEN, CollectionCaseStatus::IN_PROGRESS]);
    }
}
