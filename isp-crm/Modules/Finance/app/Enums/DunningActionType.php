<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum DunningActionType: string
{
    case REMINDER = 'reminder';
    case WARNING = 'warning';
    case SUSPENSION = 'suspension';
    case PRE_TERMINATION = 'pre_termination';
    case WRITE_OFF = 'write_off';
    case EXTERNAL_COLLECTION = 'external_collection';
}
