<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AccessControl\Entities\User;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Enums\DisputeReasonCode;
use Modules\Finance\Enums\DisputeStatus;

class InvoiceDispute extends Model
{
    protected $fillable = [
        'invoice_id',
        'customer_id',
        'reason_code',
        'description',
        'status',
        'resolution',
        'resolved_by',
        'resolved_at',
        'created_by',
    ];

    protected $casts = [
        'status' => DisputeStatus::class,
        'reason_code' => DisputeReasonCode::class,
        'resolved_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [DisputeStatus::OPEN, DisputeStatus::UNDER_REVIEW]);
    }
}
