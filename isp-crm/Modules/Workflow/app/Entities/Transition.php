<?php

declare(strict_types=1);

namespace Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\AccessControl\Entities\Role;

class Transition extends Model
{
    protected $fillable = [
        'workflow_id',
        'from_place_id',
        'to_place_id',
        'code',
        'name',
        'description',
        'from_any',
        'conditions',
        'metadata',
    ];

    protected $casts = [
        'from_any' => 'boolean',
        'conditions' => 'array',
        'metadata' => 'array',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_id');
    }

    public function fromPlace(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'from_place_id');
    }

    public function toPlace(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'to_place_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(TransitionPermission::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'transition_permissions');
    }

    public function sideEffects(): HasMany
    {
        return $this->hasMany(SideEffect::class)->orderBy('order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TransitionLog::class);
    }

    public function canExecuteFromAny(): bool
    {
        return $this->from_any;
    }

    public function scopeFromPlace($query, int $placeId)
    {
        return $query->where(function ($q) use ($placeId) {
            $q->where('from_place_id', $placeId)
              ->orWhere('from_any', true);
        });
    }
}
