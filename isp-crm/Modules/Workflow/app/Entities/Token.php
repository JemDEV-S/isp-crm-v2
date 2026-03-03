<?php

declare(strict_types=1);

namespace Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Token extends Model
{
    protected $fillable = [
        'workflow_id',
        'tokenable_type',
        'tokenable_id',
        'current_place_id',
        'context',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_id');
    }

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    public function currentPlace(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'current_place_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TransitionLog::class)->orderBy('executed_at');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isInFinalPlace(): bool
    {
        return $this->currentPlace->isFinal();
    }

    public function getCurrentPlaceCode(): string
    {
        return $this->currentPlace->code;
    }

    public function getCurrentPlaceName(): string
    {
        return $this->currentPlace->name;
    }

    public function setContext(string $key, mixed $value): self
    {
        $context = $this->context ?? [];
        $context[$key] = $value;
        $this->context = $context;
        return $this;
    }

    public function getContext(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeForEntity($query, Model $entity)
    {
        return $query->where('tokenable_type', get_class($entity))
                     ->where('tokenable_id', $entity->getKey());
    }
}
