<?php

declare(strict_types=1);

namespace Modules\Crm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CapacityReservation extends Model
{
    protected $fillable = [
        'reservable_type',
        'reservable_id',
        'lead_id',
        'feasibility_request_id',
        'status',
        'metadata',
        'expires_at',
        'released_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function feasibilityRequest(): BelongsTo
    {
        return $this->belongsTo(FeasibilityRequest::class);
    }

    public function reservable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->released_at === null && $this->expires_at->isFuture();
    }
}
