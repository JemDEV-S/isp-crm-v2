<?php

declare(strict_types=1);

namespace Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

class WorkflowDefinition extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'entity_type',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function places(): HasMany
    {
        return $this->hasMany(Place::class, 'workflow_id')->orderBy('order');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(Transition::class, 'workflow_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class, 'workflow_id');
    }

    public function getInitialPlace(): ?Place
    {
        return $this->places()->where('is_initial', true)->first();
    }

    public function getFinalPlaces(): Collection
    {
        return $this->places()->where('is_final', true)->get();
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }
}
