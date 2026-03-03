<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'work_order_id',
        'date',
        'time_slot_start',
        'time_slot_end',
        'confirmed_at',
        'confirmed_by',
        'reminder_sent_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'confirmed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\app\Models\User::class, 'confirmed_by');
    }

    public function isConfirmed(): bool
    {
        return !is_null($this->confirmed_at);
    }

    public function reminderSent(): bool
    {
        return !is_null($this->reminder_sent_at);
    }
}
