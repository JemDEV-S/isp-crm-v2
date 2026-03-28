<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\FieldOps\app\Enums\WorkOrderPriority;
use Modules\FieldOps\app\Enums\WorkOrderType as WorkOrderTypeEnum;
use Modules\Workflow\Traits\HasWorkflow;

class WorkOrder extends Model
{
    use SoftDeletes, HasWorkflow;

    protected $fillable = [
        'uuid',
        'code',
        'work_order_type_id',
        'type',
        'subscription_id',
        'customer_id',
        'address_id',
        'priority',
        'assigned_to',
        'scheduled_date',
        'scheduled_time_slot',
        'started_at',
        'completed_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'type' => WorkOrderTypeEnum::class,
        'priority' => WorkOrderPriority::class,
        'scheduled_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (WorkOrder $workOrder) {
            if (empty($workOrder->uuid)) {
                $workOrder->uuid = (string) Str::uuid();
            }
            if (empty($workOrder->code)) {
                $workOrder->code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $prefix = 'WO';
        $year = now()->year;
        $lastOrder = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastOrder ? ((int) substr($lastOrder->code, -6)) + 1 : 1;

        return sprintf('%s-%d-%06d', $prefix, $year, $number);
    }

    // Relaciones
    public function workOrderType(): BelongsTo
    {
        return $this->belongsTo(WorkOrderType::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Crm\Entities\Customer::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(\Modules\Crm\Entities\Address::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\Entities\User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\Entities\User::class, 'created_by');
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WorkOrderPhoto::class);
    }

    public function materialUsages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function checklistResponse(): HasOne
    {
        return $this->hasOne(ChecklistResponse::class);
    }

    public function technicianLocations(): HasMany
    {
        return $this->hasMany(TechnicianLocation::class);
    }

    public function validation(): HasOne
    {
        return $this->hasOne(WorkOrderValidation::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(WorkOrderException::class);
    }

    // Scopes
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopePriority($query, WorkOrderPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeScheduledBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_date', [$startDate, $endDate]);
    }

    // Helpers
    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    public function isStarted(): bool
    {
        return !is_null($this->started_at);
    }

    public function isScheduled(): bool
    {
        return !is_null($this->scheduled_date);
    }
}
