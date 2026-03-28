<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Enums\BillingIncidentType;
use Modules\Subscription\Entities\Subscription;

class BillingIncident extends Model
{
    protected $fillable = [
        'billing_job_run_id',
        'subscription_id',
        'customer_id',
        'incident_type',
        'reason',
        'metadata',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'incident_type' => BillingIncidentType::class,
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function jobRun(): BelongsTo
    {
        return $this->belongsTo(BillingJobRun::class, 'billing_job_run_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function resolve(?int $userId = null): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $userId ?? auth()->id(),
        ]);
    }
}
