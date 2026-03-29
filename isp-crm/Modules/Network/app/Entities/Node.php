<?php

declare(strict_types=1);

namespace Modules\Network\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasStatus;
use Modules\Network\Enums\NodeStatus;
use Modules\Network\Enums\NodeType;

class Node extends Model
{
    use HasStatus;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'latitude',
        'longitude',
        'altitude',
        'status',
        'description',
        'commissioned_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'type' => NodeType::class,
        'status' => NodeStatus::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'altitude' => 'decimal:2',
        'commissioned_at' => 'datetime',
    ];

    /**
     * Get the devices at this node.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Get the NAP boxes at this node.
     */
    public function napBoxes(): HasMany
    {
        return $this->hasMany(NapBox::class);
    }

    /**
     * Get fiber routes starting from this node.
     */
    public function fiberRoutesFrom(): HasMany
    {
        return $this->hasMany(FiberRoute::class, 'from_node_id');
    }

    /**
     * Get fiber routes ending at this node.
     */
    public function fiberRoutesTo(): HasMany
    {
        return $this->hasMany(FiberRoute::class, 'to_node_id');
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if node has coordinates.
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Calculate distance to another node (in meters).
     */
    public function distanceTo(Node $node): float
    {
        if (!$this->hasCoordinates() || !$node->hasCoordinates()) {
            return 0;
        }

        $earthRadius = 6371000; // meters

        $lat1 = deg2rad((float) $this->latitude);
        $lon1 = deg2rad((float) $this->longitude);
        $lat2 = deg2rad((float) $node->latitude);
        $lon2 = deg2rad((float) $node->longitude);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
