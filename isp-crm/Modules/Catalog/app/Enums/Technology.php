<?php

declare(strict_types=1);

namespace Modules\Catalog\Enums;

enum Technology: string
{
    case FIBER = 'fiber';
    case WIRELESS = 'wireless';
    case ADSL = 'adsl';
    case CABLE = 'cable';
    case SATELLITE = 'satellite';

    public function label(): string
    {
        return match ($this) {
            self::FIBER => 'Fibra Óptica',
            self::WIRELESS => 'Inalámbrico',
            self::ADSL => 'ADSL',
            self::CABLE => 'Cable',
            self::SATELLITE => 'Satelital',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FIBER => 'fiber-optic',
            self::WIRELESS => 'wifi',
            self::ADSL => 'phone',
            self::CABLE => 'cable',
            self::SATELLITE => 'satellite',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }
}
