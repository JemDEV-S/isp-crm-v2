<?php

declare(strict_types=1);

namespace Modules\Network\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Network\Enums\PortStatus;
use Modules\Network\Enums\PortType;

class DevicePort extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'device_id',
        'port_number',
        'port_name',
        'type',
        'speed_mbps',
        'status',
        'connected_device_id',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'type' => PortType::class,
        'status' => PortStatus::class,
        'port_number' => 'integer',
        'speed_mbps' => 'integer',
    ];

    /**
     * Get the device this port belongs to.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the connected device (if any).
     */
    public function connectedDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'connected_device_id');
    }

    /**
     * Scope to filter available ports.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', PortStatus::ACTIVE)
            ->whereNull('connected_device_id');
    }

    /**
     * Check if port is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === PortStatus::ACTIVE && is_null($this->connected_device_id);
    }

    /**
     * Check if port is connected to another device.
     */
    public function isConnected(): bool
    {
        return !is_null($this->connected_device_id);
    }
}
