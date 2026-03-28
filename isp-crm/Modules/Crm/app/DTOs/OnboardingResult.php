<?php

declare(strict_types=1);

namespace Modules\Crm\DTOs;

use Modules\Crm\Entities\Customer;
use Modules\FieldOps\app\Models\WorkOrder;
use Modules\Subscription\Entities\Subscription;
use Modules\Workflow\Entities\Token;

final readonly class OnboardingResult
{
    public function __construct(
        public Customer $customer,
        public Subscription $subscription,
        public WorkOrder $workOrder,
        public ?Token $workflowToken = null,
    ) {}

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'subscription_id' => $this->subscription->id,
            'work_order_id' => $this->workOrder->id,
            'workflow_token_id' => $this->workflowToken?->id,
            'workflow_place' => $this->workflowToken?->getCurrentPlaceCode(),
        ];
    }
}
