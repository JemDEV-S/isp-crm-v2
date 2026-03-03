<?php

declare(strict_types=1);

namespace Modules\Catalog\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AccessControl\Entities\User;
use Modules\Core\Contracts\Activatable;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;

class Addon extends Model implements Activatable
{
    use HasFactory, SoftDeletes, HasUuid, Auditable;

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'price',
        'is_recurring',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the plans associated with this addon.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_addon')
            ->withPivot('is_included')
            ->withTimestamps();
    }

    /**
     * Get the user who created this addon.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this addon.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get formatted price string.
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = 'S/ ' . number_format((float) $this->price, 2);
        return $this->is_recurring ? "{$price}/mes" : $price;
    }

    /**
     * Get the billing type label.
     */
    public function getBillingTypeAttribute(): string
    {
        return $this->is_recurring ? 'Recurrente' : 'Único';
    }

    /**
     * Activate the addon.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the addon.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check if the addon is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to filter only active addons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter only recurring addons.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope to filter only one-time addons.
     */
    public function scopeOneTime($query)
    {
        return $query->where('is_recurring', false);
    }
}
