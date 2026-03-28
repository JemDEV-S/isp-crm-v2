<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case YAPE = 'yape';
    case PLIN = 'plin';
    case WALLET = 'wallet';
    case CHECK = 'check';
}
