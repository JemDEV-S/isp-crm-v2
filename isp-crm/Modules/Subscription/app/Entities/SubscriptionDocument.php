<?php

declare(strict_types=1);

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AccessControl\Entities\User;
use Modules\Crm\Entities\Customer;

class SubscriptionDocument extends Model
{
    protected $fillable = [
        'subscription_id',
        'customer_id',
        'document_type',
        'document_number',
        'file_path',
        'validated_at',
        'validated_by',
        'metadata',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }
}
