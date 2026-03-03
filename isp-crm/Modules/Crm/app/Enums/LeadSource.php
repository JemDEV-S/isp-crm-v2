<?php

declare(strict_types=1);

namespace Modules\Crm\Enums;

enum LeadSource: string
{
    case WALK_IN = 'walk_in';
    case PHONE = 'phone';
    case WEBSITE = 'website';
    case REFERRAL = 'referral';
    case SOCIAL_MEDIA = 'social_media';
    case CAMPAIGN = 'campaign';

    public function label(): string
    {
        return match ($this) {
            self::WALK_IN => 'Presencial',
            self::PHONE => 'Teléfono',
            self::WEBSITE => 'Sitio Web',
            self::REFERRAL => 'Referido',
            self::SOCIAL_MEDIA => 'Redes Sociales',
            self::CAMPAIGN => 'Campaña',
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
