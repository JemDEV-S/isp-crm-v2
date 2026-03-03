<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum IpStatus: string
{
    case FREE = 'free';
    case ASSIGNED = 'assigned';
    case RESERVED = 'reserved';
    case BLACKLISTED = 'blacklisted';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::FREE => 'Libre',
            self::ASSIGNED => 'Asignada',
            self::RESERVED => 'Reservada',
            self::BLACKLISTED => 'En Lista Negra',
        };
    }

    /**
     * Get the color for the status.
     */
    public function color(): string
    {
        return match($this) {
            self::FREE => 'success',
            self::ASSIGNED => 'primary',
            self::RESERVED => 'warning',
            self::BLACKLISTED => 'danger',
        };
    }

    /**
     * Check if IP is available for assignment.
     */
    public function isAvailable(): bool
    {
        return $this === self::FREE;
    }
}
