<?php

declare(strict_types=1);

namespace Modules\Crm\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Crm\DTOs\ConvertLeadDTO;
use Modules\Crm\DTOs\OnboardingResult;
use Modules\Crm\Entities\Customer;
use Modules\Crm\Entities\Lead;
use Modules\FieldOps\app\DTOs\CreateWorkOrderDTO;
use Modules\FieldOps\app\Enums\WorkOrderType;
use Modules\FieldOps\app\Models\WorkOrder;
use Modules\FieldOps\app\Services\WorkOrderService;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Services\SubscriptionService;

class CustomerOnboardingOrchestrator
{
    public function __construct(
        protected LeadService $leadService,
        protected FeasibilityService $feasibilityService,
        protected SubscriptionService $subscriptionService,
        protected WorkOrderService $workOrderService,
    ) {}

    public function startOnboarding(int $leadId): array
    {
        $lead = Lead::with(['duplicateOf', 'feasibilityRequests', 'capacityReservations'])->findOrFail($leadId);

        return [
            'lead_id' => $lead->id,
            'is_duplicate' => $lead->is_duplicate,
            'duplicate_matches' => $this->leadService->checkDuplicates($lead),
            'feasibility_requests' => $lead->feasibilityRequests->count(),
            'active_capacity_reservations' => $lead->capacityReservations()
                ->whereNull('released_at')
                ->where('expires_at', '>', now())
                ->count(),
        ];
    }

    public function convertLeadToCustomer(ConvertLeadDTO $dto): Customer
    {
        return $this->leadService->convert($dto);
    }

    public function createSubscriptionAndWorkflow(CreateSubscriptionDTO $dto, array $workOrderData = []): OnboardingResult
    {
        return DB::transaction(function () use ($dto, $workOrderData) {
            $subscription = $this->subscriptionService->create($dto);
            $customer = $subscription->customer()->firstOrFail();
            $workOrderPayload = [
                'type' => WorkOrderType::INSTALLATION->value,
                'customer_id' => $subscription->customer_id,
                'address_id' => $subscription->address_id,
                'subscription_id' => $subscription->id,
                'priority' => $this->normalizePriority($workOrderData['priority'] ?? null),
                'assigned_to' => $workOrderData['assigned_to'] ?? null,
                'scheduled_date' => $this->normalizeScheduledDate($workOrderData['scheduled_date'] ?? null),
                'scheduled_time_slot' => $workOrderData['scheduled_time_slot'] ?? null,
                'notes' => $workOrderData['notes'] ?? $subscription->notes,
                'created_by' => $workOrderData['created_by'] ?? auth()->id() ?? 0,
            ];

            $workOrder = $this->workOrderService->create(CreateWorkOrderDTO::fromArray($workOrderPayload));

            return new OnboardingResult(
                customer: $customer,
                subscription: $subscription->fresh(['serviceInstance', 'documents']),
                workOrder: $workOrder->fresh(['workOrderType', 'appointment']),
                workflowToken: $workOrder->getActiveWorkflowToken('installation'),
            );
        });
    }

    public function handleWorkflowCompletion(int $workOrderId): void
    {
        $workOrder = WorkOrder::with(['subscription.serviceInstance', 'validation'])->findOrFail($workOrderId);

        if (!$workOrder->isInWorkflowPlace('completed') || !$workOrder->subscription) {
            return;
        }

        if ($workOrder->subscription->status === SubscriptionStatus::PENDING_INSTALLATION) {
            $this->subscriptionService->activate($workOrder->subscription);
        }
    }

    protected function normalizePriority(mixed $priority): string
    {
        if ($priority instanceof \Modules\FieldOps\app\Enums\WorkOrderPriority) {
            return $priority->value;
        }

        return is_string($priority) && $priority !== ''
            ? $priority
            : \Modules\FieldOps\app\Enums\WorkOrderPriority::NORMAL->value;
    }

    protected function normalizeScheduledDate(mixed $scheduledDate): ?string
    {
        if ($scheduledDate instanceof Carbon) {
            return $scheduledDate->toDateString();
        }

        if (is_string($scheduledDate) && trim($scheduledDate) !== '') {
            return $scheduledDate;
        }

        return null;
    }
}
