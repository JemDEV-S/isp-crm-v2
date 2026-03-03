<?php

declare(strict_types=1);

namespace Modules\Network\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpPool extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'network_cidr',
        'gateway',
        'dns_primary',
        'dns_secondary',
        'type',
        'vlan_id',
        'device_id',
        'is_active',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'vlan_id' => 'integer',
    ];

    /**
     * Get the device managing this pool.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get all IP addresses in this pool.
     */
    public function ipAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class, 'pool_id');
    }

    /**
     * Get free IP addresses.
     */
    public function freeAddresses(): HasMany
    {
        return $this->ipAddresses()->where('status', 'free');
    }

    /**
     * Get assigned IP addresses.
     */
    public function assignedAddresses(): HasMany
    {
        return $this->ipAddresses()->where('status', 'assigned');
    }

    /**
     * Scope to filter active pools.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Calculate usage percentage.
     */
    public function usagePercentage(): float
    {
        $total = $this->ipAddresses()->count();
        if ($total === 0) {
            return 0;
        }

        $assigned = $this->assignedAddresses()->count();
        return ($assigned / $total) * 100;
    }

    /**
     * Check if pool has available IPs.
     */
    public function hasAvailableIps(): bool
    {
        return $this->freeAddresses()->exists();
    }

    /**
     * Get total capacity.
     */
    public function totalCapacity(): int
    {
        return $this->ipAddresses()->count();
    }

    /**
     * Get remaining capacity.
     */
    public function remainingCapacity(): int
    {
        return $this->freeAddresses()->count();
    }
}
