<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Modules\Finance\Events\InvoicePaid;
use Modules\Finance\Services\ReconnectionService;

class EvaluateReconnection
{
    public function __construct(
        protected ReconnectionService $reconnectionService,
    ) {}

    public function handle(InvoicePaid $event): void
    {
        $this->reconnectionService->evaluateReconnection($event->invoice);
    }
}
