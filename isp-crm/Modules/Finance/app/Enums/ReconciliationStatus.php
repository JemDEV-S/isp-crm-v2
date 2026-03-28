<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum ReconciliationStatus: string
{
    case PENDING = 'pending';
    case ALLOCATED = 'allocated';
    case PARTIALLY_ALLOCATED = 'partially_allocated';
    case UNMATCHED = 'unmatched';
}
