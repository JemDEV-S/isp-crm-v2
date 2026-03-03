<?php

declare(strict_types=1);

namespace Modules\Catalog\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AccessControl\Entities\User;
use Modules\Catalog\Enums\AppliesTo;
use Modules\Catalog\Enums\DiscountType;
use Modules\Core\Contracts\Activatable;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;

class Promotion extends Model implements Activatable
{
    use HasFactory, SoftDeletes, HasUuid, Auditable;

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'applies_to',
        'min_months',
        'discount_months',
        'valid_from',
        'valid_until',
        'max_uses',
        'current_uses',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'applies_to' => AppliesTo::class,
            'min_months' => 'integer',
            'discount_months' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'max_uses' => 'integer',
            'current_uses' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the plans associated with this promotion.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_promotion')
            ->withTimestamps();
    }

    /**
     * Get the user who created this promotion.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this promotion.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the promotion is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $this->valid_from > $now) {
            return false;
        }

        if ($this->valid_until && $this->valid_until < $now) {
            return false;
        }

        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if uses are available.
     */
    public function hasUsesAvailable(): bool
    {
        if ($this->max_uses === null) {
            return true;
        }

        return $this->current_uses < $this->max_uses;
    }

    /**
     * Increment the usage counter.
     */
    public function incrementUses(): void
    {
        $this->increment('current_uses');
    }

    /**
     * Decrement the usage counter.
     */
    public function decrementUses(): void
    {
        if ($this->current_uses > 0) {
            $this->decrement('current_uses');
        }
    }

    /**
     * Calculate the discount amount for a given price.
     */
    public function calculateDiscount(float $price): float
    {
        return match ($this->discount_type) {
            DiscountType::PERCENTAGE => $price * ($this->discount_value / 100),
            DiscountType::FIXED => min($this->discount_value, $price),
        };
    }

    /**
     * Get the formatted discount value.
     */
    public function getFormattedDiscountAttribute(): string
    {
        return match ($this->discount_type) {
            DiscountType::PERCENTAGE => "{$this->discount_value}%",
            DiscountType::FIXED => 'S/ ' . number_format((float) $this->discount_value, 2),
        };
    }

    /**
     * Get remaining uses.
     */
    public function getRemainingUsesAttribute(): ?int
    {
        if ($this->max_uses === null) {
            return null;
        }

        return max(0, $this->max_uses - $this->current_uses);
    }

    /**
     * Activate the promotion.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the promotion.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check if the promotion is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to filter only active promotions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter valid promotions.
     */
    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('current_uses', '<', 'max_uses');
            });
    }

    /**
     * Scope to filter by applies_to type.
     */
    public function scopeAppliesTo($query, AppliesTo $appliesTo)
    {
        return $query->where(function ($q) use ($appliesTo) {
            $q->where('applies_to', $appliesTo)
                ->orWhere('applies_to', AppliesTo::BOTH);
        });
    }
}
