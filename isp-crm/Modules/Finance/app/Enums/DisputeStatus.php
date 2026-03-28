<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum DisputeStatus: string
{
    case OPEN = 'open';
    case UNDER_REVIEW = 'under_review';
    case RESOLVED_FAVOR_CUSTOMER = 'resolved_favor_customer';
    case RESOLVED_FAVOR_COMPANY = 'resolved_favor_company';
    case CLOSED = 'closed';
}
