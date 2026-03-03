<?php

declare(strict_types=1);

namespace Modules\AccessControl\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasStatus;

class Zone extends Model
{
    use HasStatus;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'polygon',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'polygon' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent zone.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'parent_id');
    }

    /**
     * Get the child zones.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Zone::class, 'parent_id');
    }

    /**
     * Get all users in this zone.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'zone_id');
    }

    /**
     * Get all descendant zones (recursive).
     */
    public function descendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $child->descendants());
        }

        return $descendants;
    }

    /**
     * Check if this zone has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
}
