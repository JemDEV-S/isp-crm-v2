<?php

declare(strict_types=1);

namespace Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Workflow\Enums\TriggerPoint;

class SideEffect extends Model
{
    protected $fillable = [
        'transition_id',
        'trigger_point',
        'action_class',
        'parameters',
        'order',
        'is_active',
    ];

    protected $casts = [
        'trigger_point' => TriggerPoint::class,
        'parameters' => 'array',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function transition(): BelongsTo
    {
        return $this->belongsTo(Transition::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTriggerPoint($query, TriggerPoint $triggerPoint)
    {
        return $query->where('trigger_point', $triggerPoint);
    }
}
