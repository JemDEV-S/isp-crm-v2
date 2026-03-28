<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum GenerationSource: string
{
    case SCHEDULED = 'scheduled';
    case MANUAL = 'manual';
    case ADJUSTMENT = 'adjustment';
    case MIGRATION = 'migration';
}
