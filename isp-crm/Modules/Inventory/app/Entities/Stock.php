<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    use HasFactory;

    protected $table = 'stock';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
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

    // Helpers
    public function getAvailableQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function hasStock(): bool
    {
        return $this->available_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->quantity < $this->product->min_stock;
    }

    public function canReserve(float $quantity): bool
    {
        return $this->available_quantity >= $quantity;
    }

    public function reserve(float $quantity): void
    {
        if (!$this->canReserve($quantity)) {
            throw new \Exception("Stock insuficiente para reservar. Disponible: {$this->available_quantity}");
        }

        $this->increment('reserved_quantity', $quantity);
    }

    public function unreserve(float $quantity): void
    {
        $this->decrement('reserved_quantity', $quantity);
    }

    public function add(float $quantity): void
    {
        $this->increment('quantity', $quantity);
    }

    public function subtract(float $quantity): void
    {
        if ($this->quantity < $quantity) {
            throw new \Exception("No hay suficiente stock. Disponible: {$this->quantity}");
        }

        $this->decrement('quantity', $quantity);
    }
}
