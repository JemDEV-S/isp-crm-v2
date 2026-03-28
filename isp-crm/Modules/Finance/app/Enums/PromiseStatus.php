<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum PromiseStatus: string
{
    case PENDING = 'pending';
    case FULFILLED = 'fulfilled';
    case BROKEN = 'broken';
    case CANCELLED = 'cancelled';
}
