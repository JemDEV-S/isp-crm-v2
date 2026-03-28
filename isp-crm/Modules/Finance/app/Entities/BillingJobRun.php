<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\AccessControl\Entities\User;

class BillingJobRun extends Model
{
    protected $fillable = [
        'uuid',
        'billing_period',
        'started_at',
        'completed_at',
        'status',
        'total_eligible',
        'total_processed',
        'total_invoiced',
        'total_skipped',
        'total_failed',
        'metadata',
        'triggered_by',
        'user_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
        'total_eligible' => 'integer',
        'total_processed' => 'integer',
        'total_invoiced' => 'integer',
        'total_skipped' => 'integer',
        'total_failed' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BillingJobRun $run) {
            if (empty($run->uuid)) {
                $run->uuid = (string) Str::uuid();
            }
            if (empty($run->started_at)) {
                $run->started_at = now();
            }
        });
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(BillingIncident::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'issued_by_job_run_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markCompleted(): void
    {
        $this->update([
            'completed_at' => now(),
            'status' => $this->total_failed > 0 ? 'completed_with_errors' : 'completed',
            'metadata' => array_merge($this->metadata ?? [], [
                'duration_seconds' => $this->started_at->diffInSeconds(now()),
            ]),
        ]);
    }

    public function markFailed(string $reason): void
    {
        $this->update([
            'completed_at' => now(),
            'status' => 'failed',
            'metadata' => array_merge($this->metadata ?? [], [
                'failure_reason' => $reason,
                'duration_seconds' => $this->started_at->diffInSeconds(now()),
            ]),
        ]);
    }

    public function incrementProcessed(): void
    {
        $this->increment('total_processed');
    }

    public function incrementInvoiced(): void
    {
        $this->increment('total_invoiced');
    }

    public function incrementSkipped(): void
    {
        $this->increment('total_skipped');
    }

    public function incrementFailed(): void
    {
        $this->increment('total_failed');
    }
}
