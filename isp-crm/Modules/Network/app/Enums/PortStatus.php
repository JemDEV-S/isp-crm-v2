<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum PortStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DAMAGED = 'damaged';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
            self::DAMAGED => 'Dañado',
        };
    }

    /**
     * Get the color for the status.
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'default',
            self::DAMAGED => 'danger',
        };
    }
}
