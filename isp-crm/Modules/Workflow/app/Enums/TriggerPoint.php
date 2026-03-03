<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

enum TriggerPoint: string
{
    case ON_EXIT = 'on_exit';
    case ON_ENTER = 'on_enter';
    case BEFORE = 'before';
    case AFTER = 'after';

    public function label(): string
    {
        return match ($this) {
            self::ON_EXIT => 'Al Salir',
            self::ON_ENTER => 'Al Entrar',
            self::BEFORE => 'Antes',
            self::AFTER => 'Después',
        };
    }
}
