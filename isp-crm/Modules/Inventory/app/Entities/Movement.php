<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Enums\MovementType;
use Modules\Core\Traits\HasUuid;

class Movement extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'code',
        'type',
        'product_id',
        'quantity',
        'from_warehouse_id',
        'to_warehouse_id',
        'serial_id',
        'reference_type',
        'reference_id',
        'unit_cost',
        'notes',
        'user_id',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'type' => MovementType::class,
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            if (!$movement->code) {
                $movement->code = static::generateCode();
            }
        });
    }

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(Serial::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\Entities\User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\Entities\User::class, 'approved_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeOfType($query, MovementType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where(function ($q) use ($warehouseId) {
            $q->where('from_warehouse_id', $warehouseId)
              ->orWhere('to_warehouse_id', $warehouseId);
        });
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at');
    }

    // Helpers
    public function isApproved(): bool
    {
        return !is_null($this->approved_at);
    }

    public function isPositive(): bool
    {
        return $this->type->isIncoming();
    }

    public function getTotalCostAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }

    protected static function generateCode(): string
    {
        $date = now()->format('Ymd');
        $latest = static::whereDate('created_at', today())->count();
        return "MOV-{$date}-" . str_pad((string)($latest + 1), 4, '0', STR_PAD_LEFT);
    }
}
