<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum AgingBucket: string
{
    case CURRENT = 'current';
    case D1_15 = '1-15';
    case D16_30 = '16-30';
    case D31_60 = '31-60';
    case D61_90 = '61-90';
    case D90_PLUS = '90+';

    public static function fromDays(int $days): self
    {
        return match (true) {
            $days <= 0 => self::CURRENT,
            $days <= 15 => self::D1_15,
            $days <= 30 => self::D16_30,
            $days <= 60 => self::D31_60,
            $days <= 90 => self::D61_90,
            default => self::D90_PLUS,
        };
    }
}
