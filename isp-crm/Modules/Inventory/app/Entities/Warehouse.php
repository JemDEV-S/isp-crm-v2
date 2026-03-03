<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Enums\WarehouseType;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'user_id',
        'zone_id',
        'contact_name',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'type' => WarehouseType::class,
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\Entities\User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\Entities\Zone::class);
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function movementsFrom(): HasMany
    {
        return $this->hasMany(Movement::class, 'from_warehouse_id');
    }

    public function movementsTo(): HasMany
    {
        return $this->hasMany(Movement::class, 'to_warehouse_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, WarehouseType|string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeMobile($query)
    {
        return $query->where('type', WarehouseType::MOBILE);
    }

    public function scopeCentral($query)
    {
        return $query->where('type', WarehouseType::CENTRAL);
    }

    // Helpers
    public function isMobile(): bool
    {
        return $this->type === WarehouseType::MOBILE;
    }

    public function getTotalValue(): float
    {
        return $this->stock()
            ->join('products', 'products.id', '=', 'stock.product_id')
            ->sum(\DB::raw('stock.quantity * products.unit_cost'));
    }

    public function getTotalProducts(): int
    {
        return $this->stock()->count();
    }

    public function getTotalQuantity(): float
    {
        return $this->stock()->sum('quantity');
    }
}
