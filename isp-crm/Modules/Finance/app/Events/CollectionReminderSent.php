<?php

declare(strict_types=1);

namespace Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Entities\DunningExecution;

class CollectionReminderSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DunningExecution $execution,
        public readonly string $channel,
        public readonly ?string $result = null,
    ) {}
}
