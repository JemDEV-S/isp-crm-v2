<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum NodeStatus: string
{
    case ACTIVE = 'active';
    case MAINTENANCE = 'maintenance';
    case INACTIVE = 'inactive';

    /**
     * Get the label for the node status.
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Activa',
            self::MAINTENANCE => 'En mantenimiento',
            self::INACTIVE => 'Inactiva',
        };
    }
}
