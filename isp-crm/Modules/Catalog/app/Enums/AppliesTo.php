<?php

declare(strict_types=1);

namespace Modules\Catalog\Enums;

enum AppliesTo: string
{
    case MONTHLY = 'monthly';
    case INSTALLATION = 'installation';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Mensualidad',
            self::INSTALLATION => 'Instalación',
            self::BOTH => 'Ambos',
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
