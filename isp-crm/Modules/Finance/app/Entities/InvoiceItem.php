<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Enums\InvoiceItemType;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'code',
        'type',
        'concept',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'tax',
        'billing_period_start',
        'billing_period_end',
        'source_reference',
    ];

    protected $casts = [
        'type' => InvoiceItemType::class,
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
