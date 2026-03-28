<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FieldOps\app\Enums\ExceptionType;

class WorkOrderException extends Model
{
    protected $fillable = [
        'work_order_id',
        'exception_type',
        'causal_code',
        'description',
        'resolved_at',
    ];

    protected $casts = [
        'exception_type' => ExceptionType::class,
        'resolved_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
