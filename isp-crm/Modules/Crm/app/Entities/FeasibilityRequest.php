<?php

declare(strict_types=1);

namespace Modules\Crm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeasibilityRequest extends Model
{
    protected $fillable = [
        'lead_id',
        'address_id',
        'status',
        'latitude',
        'longitude',
        'radius_meters',
        'result_data',
        'requested_at',
        'resolved_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'radius_meters' => 'integer',
        'result_data' => 'array',
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(CapacityReservation::class);
    }
}
