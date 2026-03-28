<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Enums\DunningActionType;

class DunningStage extends Model
{
    protected $fillable = [
        'dunning_policy_id',
        'stage_order',
        'name',
        'code',
        'action_type',
        'min_days_overdue',
        'max_days_overdue',
        'channels',
        'template_code',
        'auto_execute',
        'requires_approval',
        'metadata',
    ];

    protected $casts = [
        'stage_order' => 'integer',
        'min_days_overdue' => 'integer',
        'max_days_overdue' => 'integer',
        'channels' => 'array',
        'metadata' => 'array',
        'auto_execute' => 'boolean',
        'requires_approval' => 'boolean',
        'action_type' => DunningActionType::class,
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(DunningPolicy::class, 'dunning_policy_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(DunningExecution::class);
    }

    public function matchesDays(int $daysOverdue): bool
    {
        return $daysOverdue >= $this->min_days_overdue && $daysOverdue <= $this->max_days_overdue;
    }
}
