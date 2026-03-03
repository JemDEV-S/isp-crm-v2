<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum DeviceType: string
{
    case ROUTER = 'router';
    case OLT = 'olt';
    case SWITCH = 'switch';
    case AP = 'ap';
    case ONT = 'ont';

    /**
     * Get the label for the device type.
     */
    public function label(): string
    {
        return match($this) {
            self::ROUTER => 'Router',
            self::OLT => 'OLT',
            self::SWITCH => 'Switch',
            self::AP => 'Access Point',
            self::ONT => 'ONT (Cliente)',
        };
    }

    /**
     * Check if this device type can have ports.
     */
    public function hasPorts(): bool
    {
        return in_array($this, [self::ROUTER, self::OLT, self::SWITCH]);
    }

    /**
     * Check if this device type requires API configuration.
     */
    public function requiresApi(): bool
    {
        return in_array($this, [self::ROUTER, self::OLT]);
    }
}
