<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialUsage extends Model
{
    protected $table = 'material_usage';

    protected $fillable = [
        'work_order_id',
        'product_id',
        'serial_id',
        'quantity',
        'warehouse_id',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\app\Models\Product::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\app\Models\Serial::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\app\Models\Warehouse::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccessControl\app\Models\User::class, 'recorded_by');
    }
}
