<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Traits\HasScope;
use Modules\Core\Traits\Auditable;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasScope, Auditable;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'unit_of_measure',
        'min_stock',
        'requires_serial',
        'unit_cost',
        'brand',
        'model',
        'specifications',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'requires_serial' => 'boolean',
        'is_active' => 'boolean',
        'unit_cost' => 'decimal:2',
        'min_stock' => 'integer',
        'specifications' => 'array',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(Serial::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\Entities\User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequiresSerial($query)
    {
        return $query->where('requires_serial', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // Helpers
    public function getTotalStockAttribute(): float
    {
        return $this->stock()->sum('quantity');
    }

    public function getAvailableStockAttribute(): float
    {
        return $this->stock()->sum(\DB::raw('quantity - reserved_quantity'));
    }

    public function hasLowStock(): bool
    {
        return $this->available_stock < $this->min_stock;
    }

    public function getStockInWarehouse(int $warehouseId): ?Stock
    {
        return $this->stock()->where('warehouse_id', $warehouseId)->first();
    }
}
