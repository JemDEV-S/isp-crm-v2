<?php

declare(strict_types=1);

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AccessControl\Entities\User;
use Modules\Subscription\Enums\SubscriptionStatus;

class SubscriptionStatusHistory extends Model
{
    protected $table = 'subscription_status_history';

    protected $fillable = [
        'subscription_id',
        'from_status',
        'to_status',
        'reason',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'from_status' => SubscriptionStatus::class,
        'to_status' => SubscriptionStatus::class,
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
