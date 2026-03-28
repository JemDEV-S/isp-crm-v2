<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Modules\Finance\Services\InvoiceService;
use Modules\Subscription\Events\SubscriptionActivated;

class GenerateInitialInvoice
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function handle(SubscriptionActivated $event): void
    {
        $this->invoiceService->generateInitialInvoice($event->subscription->id);
    }
}
