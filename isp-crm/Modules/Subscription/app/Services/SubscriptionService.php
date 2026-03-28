<?php

declare(strict_types=1);

namespace Modules\Subscription\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Addon;
use Modules\Catalog\Entities\Promotion;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Entities\ServiceInstance;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionStatusHistory;
use Modules\Subscription\Enums\ProvisionStatus;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\SubscriptionCancelled;
use Modules\Subscription\Events\SubscriptionCreated;
use Modules\Subscription\Events\SubscriptionReactivated;
use Modules\Subscription\Events\SubscriptionSuspended;

class SubscriptionService
{
    public function __construct(
        protected SubscriptionContractService $subscriptionContractService,
        protected ActivationService $activationService,
        protected PlanChangeService $planChangeService,
    ) {}

    public function create(CreateSubscriptionDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $plan = $dto->getPlan();

            $subscription = Subscription::create([
                'customer_id' => $dto->customerId,
                'plan_id' => $dto->planId,
                'address_id' => $dto->addressId,
                'status' => SubscriptionStatus::DRAFT,
                'billing_day' => $dto->billingDay,
                'billing_cycle' => $dto->billingCycle,
                'start_date' => $dto->startDate,
                'contracted_months' => $dto->contractedMonths,
                'monthly_price' => $plan->price,
                'installation_fee' => $plan->installation_fee,
                'promotion_id' => $dto->promotionId,
                'notes' => $dto->notes,
            ]);

            ServiceInstance::create([
                'subscription_id' => $subscription->id,
                'pppoe_user' => $this->generatePppoeUser($subscription),
                'pppoe_password' => Str::random(12),
                'provision_status' => ProvisionStatus::PENDING,
            ]);

            foreach ($dto->addons as $addonId) {
                $addon = Addon::find($addonId);
                if ($addon) {
                    $subscription->addons()->attach($addonId, [
                        'price' => $addon->price,
                        'start_date' => $dto->startDate,
                    ]);
                }
            }

            if ($dto->promotionId) {
                $this->applyPromotion($subscription, $dto->promotionId);
            }

            $subscription = $this->subscriptionContractService->initializeForSubscription($subscription->fresh([
                'plan.parameters',
                'addons',
                'promotion',
            ]));

            $this->changeStatus($subscription, SubscriptionStatus::PENDING_INSTALLATION, 'Suscripcion creada');

            event(new SubscriptionCreated($subscription));

            return $subscription->fresh(['customer', 'plan', 'address', 'serviceInstance', 'addons', 'documents']);
        });
    }

    public function activate(Subscription $subscription): Subscription
    {
        return $this->activationService->activate($subscription);
    }

    public function suspend(Subscription $subscription, string $reason, bool $voluntary = false): Subscription
    {
        if (!$subscription->canBeSuspended()) {
            throw new \DomainException('Esta suscripcion no puede ser suspendida');
        }

        $newStatus = $voluntary ? SubscriptionStatus::SUSPENDED_VOLUNTARY : SubscriptionStatus::SUSPENDED;
        $this->changeStatus($subscription, $newStatus, $reason);

        event(new SubscriptionSuspended($subscription, $reason));

        return $subscription->fresh();
    }

    public function reactivate(Subscription $subscription, string $reason = 'Reactivacion manual'): Subscription
    {
        if (!$subscription->canBeReactivated()) {
            throw new \DomainException('Esta suscripcion no puede ser reactivada');
        }

        $this->changeStatus($subscription, SubscriptionStatus::ACTIVE, $reason);

        event(new SubscriptionReactivated($subscription));

        return $subscription->fresh();
    }

    public function cancel(Subscription $subscription, string $reason): Subscription
    {
        if (!$subscription->canBeCancelled()) {
            throw new \DomainException('Esta suscripcion no puede ser cancelada');
        }

        $this->changeStatus($subscription, SubscriptionStatus::CANCELLED, $reason);

        $subscription->update([
            'end_date' => now(),
        ]);

        event(new SubscriptionCancelled($subscription, $reason));

        return $subscription->fresh();
    }

    public function changePlan(Subscription $subscription, int $newPlanId, bool $immediate = true): Subscription
    {
        $this->planChangeService->request(new \Modules\Subscription\DTOs\RequestPlanChangeDTO(
            subscriptionId: $subscription->id,
            newPlanId: $newPlanId,
            effectiveMode: $immediate ? 'immediate' : 'next_cycle',
        ));

        return $subscription->fresh(['plan']);
    }

    public function addNote(Subscription $subscription, string $content, bool $internal = false): void
    {
        $subscription->notes()->create([
            'content' => $content,
            'is_internal' => $internal,
        ]);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Subscription::query()
            ->with(['customer', 'plan', 'address.zone', 'serviceInstance']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        if (!empty($filters['billing_day'])) {
            $query->where('billing_day', $filters['billing_day']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getStats(): array
    {
        return [
            'total' => Subscription::count(),
            'active' => Subscription::status(SubscriptionStatus::ACTIVE)->count(),
            'suspended' => Subscription::suspended()->count(),
            'pending_installation' => Subscription::status(SubscriptionStatus::PENDING_INSTALLATION)->count(),
            'cancelled' => Subscription::status(SubscriptionStatus::CANCELLED)->count(),
        ];
    }

    protected function changeStatus(Subscription $subscription, SubscriptionStatus $newStatus, string $reason): void
    {
        $oldStatus = $subscription->status;

        SubscriptionStatusHistory::create([
            'subscription_id' => $subscription->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
            'user_id' => auth()->id(),
        ]);

        $subscription->update(['status' => $newStatus]);
    }

    protected function applyPromotion(Subscription $subscription, int $promotionId): void
    {
        $promotion = Promotion::findOrFail($promotionId);

        if (!$promotion->isValid()) {
            throw new \DomainException('La promocion no es valida o ha expirado');
        }

        $discountPercentage = 0;
        $discountMonths = 0;

        if ($promotion->discount_type->value === 'percentage') {
            $discountPercentage = $promotion->discount_value;
            $discountMonths = $promotion->min_months ?? 0;
        }

        $subscription->update([
            'discount_percentage' => $discountPercentage,
            'discount_months_remaining' => $discountMonths,
        ]);

        $promotion->increment('current_uses');
    }

    protected function generatePppoeUser(Subscription $subscription): string
    {
        return 'user_' . strtolower(Str::random(8));
    }
}
