<?php

declare(strict_types=1);

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Entities\Serial;
use Modules\Network\Entities\IpAddress;
use Modules\Network\Entities\NapPort;
use Modules\Subscription\Enums\ProvisionStatus;

class ServiceInstance extends Model
{
    protected $fillable = [
        'subscription_id',
        'pppoe_user',
        'pppoe_password',
        'ip_address_id',
        'serial_id',
        'nap_port_id',
        'onu_serial',
        'provision_status',
        'provisioned_at',
        'last_connection_at',
        'metadata',
        'provision_data',
    ];

    protected $casts = [
        'provision_status' => ProvisionStatus::class,
        'provisioned_at' => 'datetime',
        'last_connection_at' => 'datetime',
        'metadata' => 'array',
        'provision_data' => 'array',
    ];

    protected $hidden = [
        'pppoe_password',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function ipAddress(): BelongsTo
    {
        return $this->belongsTo(IpAddress::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(Serial::class);
    }

    public function napPort(): BelongsTo
    {
        return $this->belongsTo(NapPort::class);
    }

    public function isProvisioned(): bool
    {
        return $this->provision_status === ProvisionStatus::ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->provision_status === ProvisionStatus::PENDING;
    }

    public function isSuspended(): bool
    {
        return $this->provision_status === ProvisionStatus::SUSPENDED;
    }

    public function hasFailed(): bool
    {
        return $this->provision_status === ProvisionStatus::FAILED;
    }

    public function getIpAddressValue(): ?string
    {
        return $this->ipAddress?->address;
    }

    public function setMetadata(string $key, mixed $value): self
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        return $this;
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function getProvisionData(string $key, mixed $default = null): mixed
    {
        return $this->provision_data[$key] ?? $default;
    }

    public function hasProvisionData(): bool
    {
        return !empty($this->provision_data);
    }

    public function scopeProvisioned($query)
    {
        return $query->where('provision_status', ProvisionStatus::ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('provision_status', ProvisionStatus::PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('provision_status', ProvisionStatus::FAILED);
    }
}
