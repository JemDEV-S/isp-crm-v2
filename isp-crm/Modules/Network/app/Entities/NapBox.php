<?php

declare(strict_types=1);

namespace Modules\Network\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasStatus;

class NapBox extends Model
{
    use HasStatus;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'node_id',
        'code',
        'name',
        'type',
        'latitude',
        'longitude',
        'address',
        'total_ports',
        'status',
        'installed_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'total_ports' => 'integer',
        'installed_at' => 'datetime',
    ];

    /**
     * Get the node where this NAP box is located.
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Get the ports of this NAP box.
     */
    public function ports(): HasMany
    {
        return $this->hasMany(NapPort::class);
    }

    /**
     * Get occupied ports.
     */
    public function occupiedPorts(): HasMany
    {
        return $this->ports()->where('status', 'occupied');
    }

    /**
     * Get free ports.
     */
    public function freePorts(): HasMany
    {
        return $this->ports()->where('status', 'free');
    }

    /**
     * Calculate occupancy percentage.
     */
    public function occupancyPercentage(): float
    {
        $occupied = $this->occupiedPorts()->count();
        return ($occupied / $this->total_ports) * 100;
    }

    /**
     * Check if NAP has available ports.
     */
    public function hasAvailablePorts(): bool
    {
        return $this->freePorts()->exists();
    }

    /**
     * Calculate distance to coordinates (in meters).
     */
    public function distanceTo(float $latitude, float $longitude): float
    {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad((float) $this->latitude);
        $lon1 = deg2rad((float) $this->longitude);
        $lat2 = deg2rad($latitude);
        $lon2 = deg2rad($longitude);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Scope to find nearby NAP boxes.
     */
    public function scopeNearby($query, float $latitude, float $longitude, int $radiusMeters = 500)
    {
        // Nota: Esta es una implementación simple. Para producción considera usar
        // consultas espaciales de MySQL o PostGIS
        return $query->where('status', 'active')
            ->get()
            ->filter(function ($nap) use ($latitude, $longitude, $radiusMeters) {
                return $nap->distanceTo($latitude, $longitude) <= $radiusMeters;
            });
    }
}
