<?php

declare(strict_types=1);

namespace Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    protected $fillable = [
        'workflow_id',
        'code',
        'name',
        'color',
        'is_initial',
        'is_final',
        'order',
        'metadata',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
        'order' => 'integer',
        'metadata' => 'array',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_id');
    }

    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(Transition::class, 'from_place_id');
    }

    public function incomingTransitions(): HasMany
    {
        return $this->hasMany(Transition::class, 'to_place_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class, 'current_place_id');
    }

    public function isInitial(): bool
    {
        return $this->is_initial;
    }

    public function isFinal(): bool
    {
        return $this->is_final;
    }

    public function scopeInitial($query)
    {
        return $query->where('is_initial', true);
    }

    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }
}
