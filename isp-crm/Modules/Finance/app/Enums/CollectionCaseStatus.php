<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum CollectionCaseStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RECOVERED = 'recovered';
    case WRITTEN_OFF = 'written_off';
    case SENT_EXTERNAL = 'sent_external';
}
