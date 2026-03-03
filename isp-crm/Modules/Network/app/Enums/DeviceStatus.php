<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum DeviceStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
    case DECOMMISSIONED = 'decommissioned';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
            self::MAINTENANCE => 'Mantenimiento',
            self::DECOMMISSIONED => 'Dado de Baja',
        };
    }

    /**
     * Get the color for the status.
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'danger',
            self::MAINTENANCE => 'warning',
            self::DECOMMISSIONED => 'default',
        };
    }

    /**
     * Check if device is operational.
     */
    public function isOperational(): bool
    {
        return $this === self::ACTIVE;
    }
}
