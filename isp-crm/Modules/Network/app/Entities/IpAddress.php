<?php

declare(strict_types=1);

namespace Modules\Network\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Network\Enums\IpStatus;

class IpAddress extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'pool_id',
        'address',
        'status',
        'subscription_id',
        'assigned_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => IpStatus::class,
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the pool this IP belongs to.
     */
    public function pool(): BelongsTo
    {
        return $this->belongsTo(IpPool::class);
    }

    /**
     * Scope to filter free addresses.
     */
    public function scopeFree($query)
    {
        return $query->where('status', IpStatus::FREE);
    }

    /**
     * Scope to filter assigned addresses.
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', IpStatus::ASSIGNED);
    }

    /**
     * Scope to filter by pool.
     */
    public function scopeInPool($query, int $poolId)
    {
        return $query->where('pool_id', $poolId);
    }

    /**
     * Check if IP is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === IpStatus::FREE;
    }

    /**
     * Check if IP is assigned.
     */
    public function isAssigned(): bool
    {
        return $this->status === IpStatus::ASSIGNED;
    }

    /**
     * Assign IP to subscription.
     */
    public function assignTo(int $subscriptionId): void
    {
        $this->update([
            'status' => IpStatus::ASSIGNED,
            'subscription_id' => $subscriptionId,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Release IP.
     */
    public function release(): void
    {
        $this->update([
            'status' => IpStatus::FREE,
            'subscription_id' => null,
            'assigned_at' => null,
        ]);
    }

    /**
     * Reserve IP.
     */
    public function reserve(string $notes = null): void
    {
        $this->update([
            'status' => IpStatus::RESERVED,
            'notes' => $notes,
        ]);
    }

    /**
     * Blacklist IP.
     */
    public function blacklist(string $reason): void
    {
        $this->update([
            'status' => IpStatus::BLACKLISTED,
            'notes' => $reason,
        ]);
    }
}
