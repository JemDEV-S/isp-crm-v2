<?php

declare(strict_types=1);

namespace Modules\Subscription\Enums;

enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case BIMONTHLY = 'bimonthly';
    case QUARTERLY = 'quarterly';
    case ANNUAL = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Mensual',
            self::BIMONTHLY => 'Bimestral',
            self::QUARTERLY => 'Trimestral',
            self::ANNUAL => 'Anual',
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::BIMONTHLY => 2,
            self::QUARTERLY => 3,
            self::ANNUAL => 12,
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'months' => $case->months(),
        ], self::cases());
    }
}
