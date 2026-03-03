<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Enums\SerialStatus;

class Serial extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'serial_number',
        'mac_address',
        'warehouse_id',
        'status',
        'subscription_id',
        'purchase_date',
        'warranty_until',
        'notes',
    ];

    protected $casts = [
        'status' => SerialStatus::class,
        'purchase_date' => 'date',
        'warranty_until' => 'date',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', SerialStatus::IN_STOCK);
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', SerialStatus::ASSIGNED);
    }

    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Helpers
    public function isAvailable(): bool
    {
        return $this->status === SerialStatus::IN_STOCK;
    }

    public function isAssigned(): bool
    {
        return $this->status === SerialStatus::ASSIGNED;
    }

    public function isDamaged(): bool
    {
        return $this->status === SerialStatus::DAMAGED;
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_until && $this->warranty_until->isFuture();
    }

    public function assign(int $subscriptionId): void
    {
        $this->update([
            'status' => SerialStatus::ASSIGNED,
            'subscription_id' => $subscriptionId,
        ]);
    }

    public function unassign(): void
    {
        $this->update([
            'status' => SerialStatus::IN_STOCK,
            'subscription_id' => null,
        ]);
    }

    public function markAsDamaged(string $notes = null): void
    {
        $this->update([
            'status' => SerialStatus::DAMAGED,
            'notes' => $notes ?? $this->notes,
        ]);
    }
}
