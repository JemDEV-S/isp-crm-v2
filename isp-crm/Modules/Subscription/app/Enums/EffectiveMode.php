<?php

namespace Modules\Subscription\Enums;

enum EffectiveMode: string
{
    case IMMEDIATE = 'immediate';
    case NEXT_CYCLE = 'next_cycle';
    case SCHEDULED = 'scheduled';

    public function label(): string
    {
        return match ($this) {
            self::IMMEDIATE => 'Inmediato',
            self::NEXT_CYCLE => 'Próximo ciclo',
            self::SCHEDULED => 'Programado',
        };
    }
}
