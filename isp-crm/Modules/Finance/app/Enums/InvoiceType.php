<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum InvoiceType: string
{
    case INITIAL = 'initial';
    case MONTHLY = 'monthly';
    case ADJUSTMENT = 'adjustment';
    case CREDIT_NOTE = 'credit_note';
    case PROFORMA = 'proforma';
}
