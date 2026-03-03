<?php

declare(strict_types=1);

namespace Modules\Crm\Enums;

enum ContactType: string
{
    case PHONE = 'phone';
    case EMAIL = 'email';
    case WHATSAPP = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::PHONE => 'Teléfono',
            self::EMAIL => 'Email',
            self::WHATSAPP => 'WhatsApp',
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
