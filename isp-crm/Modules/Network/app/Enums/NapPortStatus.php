<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum NapPortStatus: string
{
    case FREE = 'free';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case DAMAGED = 'damaged';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::FREE => 'Libre',
            self::OCCUPIED => 'Ocupado',
            self::RESERVED => 'Reservado',
            self::DAMAGED => 'Dañado',
        };
    }

    /**
     * Get the color for the status.
     */
    public function color(): string
    {
        return match($this) {
            self::FREE => 'success',
            self::OCCUPIED => 'primary',
            self::RESERVED => 'warning',
            self::DAMAGED => 'danger',
        };
    }

    /**
     * Check if port is available.
     */
    public function isAvailable(): bool
    {
        return $this === self::FREE;
    }
}
