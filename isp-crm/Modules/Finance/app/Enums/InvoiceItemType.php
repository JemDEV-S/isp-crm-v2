<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum InvoiceItemType: string
{
    case SERVICE = 'service';
    case ADDON = 'addon';
    case DISCOUNT = 'discount';
    case TAX = 'tax';
    case PRORATION = 'proration';
    case ADJUSTMENT = 'adjustment';
    case INSTALLATION = 'installation';
}
