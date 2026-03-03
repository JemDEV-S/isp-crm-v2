<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovementRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'product_id',
        'quantity_requested',
        'quantity_approved',
        'serial_id',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_approved' => 'decimal:2',
    ];

    // Relationships
    public function request(): BelongsTo
    {
        return $this->belongsTo(MovementRequest::class, 'request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(Serial::class);
    }
}
