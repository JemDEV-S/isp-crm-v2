<?php

namespace Modules\Subscription\Enums;

enum PlanChangeType: string
{
    case UPGRADE = 'upgrade';
    case DOWNGRADE = 'downgrade';
    case LATERAL = 'lateral';

    public static function determine(float $oldPrice, float $newPrice): self
    {
        if ($newPrice > $oldPrice) return self::UPGRADE;
        if ($newPrice < $oldPrice) return self::DOWNGRADE;
        return self::LATERAL;
    }

    public function label(): string
    {
        return match ($this) {
            self::UPGRADE => 'Upgrade',
            self::DOWNGRADE => 'Downgrade',
            self::LATERAL => 'Cambio lateral',
        };
    }
}
