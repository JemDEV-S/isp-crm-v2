<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum WalletTransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}
