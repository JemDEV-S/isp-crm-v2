<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderValidation extends Model
{
    protected $fillable = [
        'work_order_id',
        'validator_id',
        'status',
        'criteria_checked',
        'observations',
        'validated_at',
    ];

    protected $casts = [
        'criteria_checked' => 'array',
        'observations' => 'array',
        'validated_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
