<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'items',
        'is_active',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    public function workOrderTypes(): HasMany
    {
        return $this->hasMany(WorkOrderType::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ChecklistResponse::class);
    }
}
