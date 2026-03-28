<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVERSED = 'reversed';
    case REFUNDED = 'refunded';
}
