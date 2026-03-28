<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case SENT = 'sent';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case PENDING_TAX_SUBMISSION = 'pending_tax_submission';
}
