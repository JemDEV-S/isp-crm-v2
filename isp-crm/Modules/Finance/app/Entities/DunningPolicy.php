<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DunningPolicy extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_default',
        'is_active',
        'applies_to',
        'applies_to_value',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(DunningStage::class)->orderBy('stage_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
