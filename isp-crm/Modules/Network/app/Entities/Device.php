<?php

declare(strict_types=1);

namespace Modules\Network\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasStatus;
use Modules\Network\Enums\DeviceType;

class Device extends Model
{
    use HasStatus;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'node_id',
        'type',
        'brand',
        'model',
        'serial_number',
        'ip_address',
        'mac_address',
        'firmware_version',
        'snmp_community',
        'api_port',
        'api_user',
        'api_password_encrypted',
        'status',
        'last_seen_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'type' => DeviceType::class,
        'last_seen_at' => 'datetime',
        'api_port' => 'integer',
    ];

    /**
     * The attributes that should be hidden.
     */
    protected $hidden = [
        'api_password_encrypted',
        'snmp_community',
    ];

    /**
     * Get the node where this device is located.
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Get the ports of this device.
     */
    public function ports(): HasMany
    {
        return $this->hasMany(DevicePort::class);
    }

    /**
     * Get IP pools managed by this device.
     */
    public function ipPools(): HasMany
    {
        return $this->hasMany(IpPool::class);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, DeviceType|string $type)
    {
        if ($type instanceof DeviceType) {
            $type = $type->value;
        }
        return $query->where('type', $type);
    }

    /**
     * Scope to filter active devices.
     */
    public function scopeOperational($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if device is online (seen in last 5 minutes).
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->diffInMinutes(now()) < 5;
    }

    /**
     * Check if device requires API configuration.
     */
    public function requiresApi(): bool
    {
        return in_array($this->type, [DeviceType::ROUTER, DeviceType::OLT]);
    }

    /**
     * Check if device has API configured.
     */
    public function hasApiConfigured(): bool
    {
        return !empty($this->ip_address)
            && !empty($this->api_user)
            && !empty($this->api_password_encrypted);
    }

    /**
     * Get decrypted API password.
     */
    public function getApiPassword(): ?string
    {
        if (empty($this->api_password_encrypted)) {
            return null;
        }

        return decrypt($this->api_password_encrypted);
    }

    /**
     * Set encrypted API password.
     */
    public function setApiPassword(string $password): void
    {
        $this->api_password_encrypted = encrypt($password);
    }
}
