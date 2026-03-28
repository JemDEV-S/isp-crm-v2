<?php

namespace Modules\Subscription\Enums;

enum BillingAdjustmentType: string
{
    case INVOICE = 'invoice';
    case CREDIT_NOTE = 'credit_note';
    case WALLET_CREDIT = 'wallet_credit';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE => 'Factura por diferencia',
            self::CREDIT_NOTE => 'Nota de crédito',
            self::WALLET_CREDIT => 'Crédito a wallet',
            self::NONE => 'Sin ajuste',
        };
    }
}
