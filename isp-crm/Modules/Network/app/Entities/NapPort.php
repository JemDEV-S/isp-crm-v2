<?php

declare(strict_types=1);

namespace Modules\Network\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Network\Enums\NapPortStatus;

class NapPort extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nap_box_id',
        'port_number',
        'status',
        'subscription_id',
        'label',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => NapPortStatus::class,
        'port_number' => 'integer',
    ];

    /**
     * Get the NAP box this port belongs to.
     */
    public function napBox(): BelongsTo
    {
        return $this->belongsTo(NapBox::class);
    }

    /**
     * Scope to filter free ports.
     */
    public function scopeFree($query)
    {
        return $query->where('status', NapPortStatus::FREE);
    }

    /**
     * Scope to filter occupied ports.
     */
    public function scopeOccupied($query)
    {
        return $query->where('status', NapPortStatus::OCCUPIED);
    }

    /**
     * Check if port is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === NapPortStatus::FREE;
    }

    /**
     * Check if port is assigned to a subscription.
     */
    public function isAssigned(): bool
    {
        return !is_null($this->subscription_id);
    }

    /**
     * Assign port to subscription.
     */
    public function assignTo(int $subscriptionId, string $label = null): void
    {
        $this->update([
            'status' => NapPortStatus::OCCUPIED,
            'subscription_id' => $subscriptionId,
            'label' => $label,
        ]);
    }

    /**
     * Release port.
     */
    public function release(): void
    {
        $this->update([
            'status' => NapPortStatus::FREE,
            'subscription_id' => null,
            'label' => null,
        ]);
    }
}
