<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum SerialStatus: string
{
    case IN_STOCK = 'in_stock';
    case ASSIGNED = 'assigned';       // En cliente
    case IN_TRANSIT = 'in_transit';
    case DAMAGED = 'damaged';
    case RETURNED = 'returned';
    case LOST = 'lost';

    public function label(): string
    {
        return match($this) {
            self::IN_STOCK => 'En Stock',
            self::ASSIGNED => 'Asignado',
            self::IN_TRANSIT => 'En Tránsito',
            self::DAMAGED => 'Dañado',
            self::RETURNED => 'Devuelto',
            self::LOST => 'Extraviado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::IN_STOCK => 'success',
            self::ASSIGNED => 'primary',
            self::IN_TRANSIT => 'warning',
            self::DAMAGED => 'danger',
            self::RETURNED => 'info',
            self::LOST => 'danger',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::IN_STOCK;
    }
}
