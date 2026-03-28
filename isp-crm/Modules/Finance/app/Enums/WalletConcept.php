<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum WalletConcept: string
{
    case PAYMENT_EXCESS = 'payment_excess';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';
    case PLAN_CHANGE_CREDIT = 'plan_change_credit';
    case INITIAL_BALANCE = 'initial_balance';
    case PAYMENT_FROM_WALLET = 'payment_from_wallet';
}
