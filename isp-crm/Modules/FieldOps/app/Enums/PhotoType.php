<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Enums;

enum PhotoType: string
{
    case BEFORE = 'before';
    case DURING = 'during';
    case AFTER = 'after';

    public function label(): string
    {
        return match($this) {
            self::BEFORE => 'Antes',
            self::DURING => 'Durante',
            self::AFTER => 'Después',
        };
    }
}
