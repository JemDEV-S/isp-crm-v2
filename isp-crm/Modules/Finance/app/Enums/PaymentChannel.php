<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum PaymentChannel: string
{
    case OFFICE = 'office';
    case BANK = 'bank';
    case GATEWAY = 'gateway';
    case WEBHOOK = 'webhook';
    case MANUAL = 'manual';
    case WALLET = 'wallet';
}
