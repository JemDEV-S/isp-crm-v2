<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Modules\Finance\Enums\DunningActionType;
use Modules\Finance\Events\DunningStageTriggered;
use Modules\Finance\Services\CollectionCaseService;

class CreateCollectionCaseOnWriteOff
{
    public function __construct(
        protected CollectionCaseService $collectionCaseService,
    ) {}

    public function handle(DunningStageTriggered $event): void
    {
        $execution = $event->execution;

        if (!in_array($execution->action_type, [DunningActionType::WRITE_OFF, DunningActionType::EXTERNAL_COLLECTION])) {
            return;
        }

        $this->collectionCaseService->open(
            customerId: $execution->customer_id,
            subscriptionId: $execution->subscription_id,
            totalDebt: (float) $execution->amount_overdue,
        );
    }
}
