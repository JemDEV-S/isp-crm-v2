<?php

declare(strict_types=1);

namespace Modules\Catalog\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AccessControl\Entities\User;
use Modules\Catalog\Enums\Technology;
use Modules\Core\Contracts\Activatable;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Network\Entities\Device;
use Modules\Network\Entities\IpPool;

class Plan extends Model implements Activatable
{
    use HasFactory, SoftDeletes, HasUuid, Auditable;

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'technology',
        'download_speed',
        'upload_speed',
        'price',
        'installation_fee',
        'ip_pool_id',
        'device_id',
        'router_profile',
        'olt_profile',
        'burst_enabled',
        'priority',
        'is_active',
        'is_visible',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'technology' => Technology::class,
            'download_speed' => 'integer',
            'upload_speed' => 'integer',
            'price' => 'decimal:2',
            'installation_fee' => 'decimal:2',
            'burst_enabled' => 'boolean',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * Get the IP pool associated with this plan.
     */
    public function ipPool(): BelongsTo
    {
        return $this->belongsTo(IpPool::class);
    }

    /**
     * Get the device associated with this plan.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the parameters for this plan.
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(PlanParameter::class);
    }

    /**
     * Get the promotions associated with this plan.
     */
    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'plan_promotion')
            ->withTimestamps();
    }

    /**
     * Get the active promotions for this plan.
     */
    public function activePromotions(): BelongsToMany
    {
        return $this->promotions()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Get the addons associated with this plan.
     */
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'plan_addon')
            ->withPivot('is_included')
            ->withTimestamps();
    }

    /**
     * Get the included addons for this plan.
     */
    public function includedAddons(): BelongsToMany
    {
        return $this->addons()->wherePivot('is_included', true);
    }

    /**
     * Get the optional addons for this plan.
     */
    public function optionalAddons(): BelongsToMany
    {
        return $this->addons()->wherePivot('is_included', false);
    }

    /**
     * Get the user who created this plan.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this plan.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get a specific parameter value.
     */
    public function getParameter(string $key, mixed $default = null): mixed
    {
        $parameter = $this->parameters()->where('key', $key)->first();
        return $parameter?->value ?? $default;
    }

    /**
     * Set a parameter value.
     */
    public function setParameter(string $key, string $value, ?string $displayName = null): PlanParameter
    {
        return $this->parameters()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'display_name' => $displayName]
        );
    }

    /**
     * Get formatted speed string.
     */
    public function getSpeedLabelAttribute(): string
    {
        return "{$this->download_speed}/{$this->upload_speed} Mbps";
    }

    /**
     * Get formatted price string.
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'S/ ' . number_format((float) $this->price, 2);
    }

    /**
     * Get the Mikrotik rate limit string.
     */
    public function getRateLimitStringAttribute(): string
    {
        $downloadKbps = $this->download_speed * 1024;
        $uploadKbps = $this->upload_speed * 1024;
        return "{$uploadKbps}k/{$downloadKbps}k";
    }

    /**
     * Activate the plan.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the plan.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check if the plan is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to filter only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter only visible plans.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope to filter by technology.
     */
    public function scopeByTechnology($query, Technology $technology)
    {
        return $query->where('technology', $technology);
    }

    /**
     * Scope to filter plans available for public display.
     */
    public function scopePublic($query)
    {
        return $query->active()->visible();
    }
}
