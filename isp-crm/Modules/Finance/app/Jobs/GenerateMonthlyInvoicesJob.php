<?php

declare(strict_types=1);

namespace Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Services\RecurringBillingService;

class GenerateMonthlyInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        public readonly string $billingPeriod,
        public readonly string $triggeredBy = 'scheduler',
        public readonly ?int $userId = null,
    ) {}

    public function handle(RecurringBillingService $billingService): void
    {
        $billingService->runBillingCycle(
            billingPeriod: $this->billingPeriod,
            triggeredBy: $this->triggeredBy,
            userId: $this->userId,
        );
    }
}
