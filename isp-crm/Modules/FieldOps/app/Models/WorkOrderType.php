<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'workflow_code',
        'default_duration_minutes',
        'requires_materials',
        'checklist_template_id',
        'is_active',
    ];

    protected $casts = [
        'default_duration_minutes' => 'integer',
        'requires_materials' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
