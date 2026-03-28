<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Enums\DunningActionType;
use Modules\Subscription\Entities\Subscription;

class DunningExecution extends Model
{
    protected $fillable = [
        'invoice_id',
        'subscription_id',
        'customer_id',
        'dunning_stage_id',
        'action_type',
        'channel',
        'status',
        'result',
        'skip_reason',
        'days_overdue',
        'amount_overdue',
        'executed_by',
        'job_run_id',
        'metadata',
        'executed_at',
    ];

    protected $casts = [
        'days_overdue' => 'integer',
        'amount_overdue' => 'decimal:2',
        'metadata' => 'array',
        'executed_at' => 'datetime',
        'action_type' => DunningActionType::class,
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(DunningStage::class, 'dunning_stage_id');
    }

    public function wasSkipped(): bool
    {
        return $this->status === 'skipped';
    }
}
