<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum PortType: string
{
    case ETHERNET = 'ethernet';
    case GPON = 'gpon';
    case SFP = 'sfp';

    /**
     * Get the label for the port type.
     */
    public function label(): string
    {
        return match($this) {
            self::ETHERNET => 'Ethernet',
            self::GPON => 'GPON',
            self::SFP => 'SFP',
        };
    }
}
