<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Enums;

enum TimeSlot: string
{
    case MORNING = 'morning';      // 08:00 - 12:00
    case AFTERNOON = 'afternoon';  // 12:00 - 18:00
    case EVENING = 'evening';      // 18:00 - 21:00

    public function label(): string
    {
        return match($this) {
            self::MORNING => 'Mañana (08:00 - 12:00)',
            self::AFTERNOON => 'Tarde (12:00 - 18:00)',
            self::EVENING => 'Noche (18:00 - 21:00)',
        };
    }

    public function startTime(): string
    {
        return match($this) {
            self::MORNING => '08:00',
            self::AFTERNOON => '12:00',
            self::EVENING => '18:00',
        };
    }

    public function endTime(): string
    {
        return match($this) {
            self::MORNING => '12:00',
            self::AFTERNOON => '18:00',
            self::EVENING => '21:00',
        };
    }
}
