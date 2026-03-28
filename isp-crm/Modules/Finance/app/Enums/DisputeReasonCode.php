<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum DisputeReasonCode: string
{
    case INCORRECT_AMOUNT = 'incorrect_amount';
    case DUPLICATE_CHARGE = 'duplicate';
    case SERVICE_ISSUE = 'service_issue';
    case BILLING_ERROR = 'billing_error';
    case UNAUTHORIZED_CHARGE = 'unauthorized';
    case OTHER = 'other';
}
