<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum BillingIncidentType: string
{
    case SKIPPED = 'skipped';
    case FAILED = 'failed';
    case DUPLICATE = 'duplicate';
    case DATA_INCOMPLETE = 'data_incomplete';
    case TAX_FAILED = 'tax_failed';
    case SUSPENDED_NO_CHARGE = 'suspended_no_charge';
}
