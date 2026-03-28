<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Modules\Crm\Events\CustomerCreated;
use Modules\Finance\Services\WalletService;

class CreateWalletForCustomer
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    public function handle(CustomerCreated $event): void
    {
        $this->walletService->createForCustomer($event->customer);
    }
}
