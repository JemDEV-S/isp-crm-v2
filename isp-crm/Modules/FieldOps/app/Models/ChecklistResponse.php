<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistResponse extends Model
{
    protected $fillable = [
        'work_order_id',
        'checklist_template_id',
        'responses',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'responses' => 'array',
        'completed_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\Entities\User::class, 'completed_by');
    }

    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }
}
