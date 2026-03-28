<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FieldOps\app\Enums\PhotoType;

class WorkOrderPhoto extends Model
{
    protected $fillable = [
        'work_order_id',
        'type',
        'file_path',
        'caption',
        'latitude',
        'longitude',
        'taken_at',
        'uploaded_by',
    ];

    protected $casts = [
        'type' => PhotoType::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'taken_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\Entities\User::class, 'uploaded_by');
    }

    public function hasLocation(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }
}
