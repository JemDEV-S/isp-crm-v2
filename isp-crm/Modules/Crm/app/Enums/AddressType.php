<?php

declare(strict_types=1);

namespace Modules\Crm\Enums;

enum AddressType: string
{
    case SERVICE = 'service';
    case BILLING = 'billing';

    public function label(): string
    {
        return match ($this) {
            self::SERVICE => 'Servicio',
            self::BILLING => 'Facturación',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
