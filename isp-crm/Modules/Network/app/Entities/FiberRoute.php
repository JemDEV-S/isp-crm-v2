<?php

declare(strict_types=1);

namespace Modules\Network\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasStatus;

class FiberRoute extends Model
{
    use HasStatus;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'from_node_id',
        'to_node_id',
        'distance_meters',
        'fiber_count',
        'route_geojson',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'distance_meters' => 'integer',
        'fiber_count' => 'integer',
        'route_geojson' => 'array',
    ];

    /**
     * Get the starting node.
     */
    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'from_node_id');
    }

    /**
     * Get the ending node.
     */
    public function toNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'to_node_id');
    }

    /**
     * Get distance in kilometers.
     */
    public function getDistanceKmAttribute(): float
    {
        return round($this->distance_meters / 1000, 2);
    }

    /**
     * Scope to find routes from a node.
     */
    public function scopeFrom($query, int $nodeId)
    {
        return $query->where('from_node_id', $nodeId);
    }

    /**
     * Scope to find routes to a node.
     */
    public function scopeTo($query, int $nodeId)
    {
        return $query->where('to_node_id', $nodeId);
    }

    /**
     * Scope to find routes between two nodes.
     */
    public function scopeBetween($query, int $nodeId1, int $nodeId2)
    {
        return $query->where(function ($q) use ($nodeId1, $nodeId2) {
            $q->where('from_node_id', $nodeId1)->where('to_node_id', $nodeId2);
        })->orWhere(function ($q) use ($nodeId1, $nodeId2) {
            $q->where('from_node_id', $nodeId2)->where('to_node_id', $nodeId1);
        });
    }
}
