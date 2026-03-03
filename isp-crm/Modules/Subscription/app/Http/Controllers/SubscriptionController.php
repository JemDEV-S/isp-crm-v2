<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Enums\BillingCycle;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Services\SubscriptionService;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $subscriptions = $this->subscriptionService->paginate(
            $request->only(['status', 'customer_id', 'plan_id', 'billing_day', 'search']),
            $request->integer('per_page', 15)
        );

        return response()->json([
            'data' => $subscriptions->items(),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'plan_id' => 'required|exists:plans,id',
            'address_id' => 'required|exists:addresses,id',
            'billing_day' => 'required|integer|min:1|max:28',
            'billing_cycle' => 'nullable|string|in:' . implode(',', array_column(BillingCycle::cases(), 'value')),
            'start_date' => 'nullable|date',
            'promotion_id' => 'nullable|exists:promotions,id',
            'addons' => 'nullable|array',
            'addons.*' => 'exists:addons,id',
            'contracted_months' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            $subscription = $this->subscriptionService->create(CreateSubscriptionDTO::fromArray($validated));

            return response()->json([
                'message' => 'Suscripción creada exitosamente',
                'data' => $subscription,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Subscription $subscription): JsonResponse
    {
        return response()->json([
            'data' => $subscription->load([
                'customer',
                'plan.parameters',
                'address.zone',
                'serviceInstance.ipAddress',
                'serviceInstance.napPort',
                'addons',
                'promotion',
                'statusHistory.user',
                'notes.user',
            ]),
        ]);
    }

    public function activate(Subscription $subscription): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->activate($subscription);

            return response()->json([
                'message' => 'Suscripción activada exitosamente',
                'data' => $subscription,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function suspend(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'voluntary' => 'nullable|boolean',
        ]);

        try {
            $subscription = $this->subscriptionService->suspend(
                $subscription,
                $validated['reason'],
                $validated['voluntary'] ?? false
            );

            return response()->json([
                'message' => 'Suscripción suspendida exitosamente',
                'data' => $subscription,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reactivate(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $subscription = $this->subscriptionService->reactivate(
                $subscription,
                $validated['reason'] ?? 'Reactivación manual'
            );

            return response()->json([
                'message' => 'Suscripción reactivada exitosamente',
                'data' => $subscription,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $subscription = $this->subscriptionService->cancel($subscription, $validated['reason']);

            return response()->json([
                'message' => 'Suscripción cancelada exitosamente',
                'data' => $subscription,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function changePlan(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'immediate' => 'nullable|boolean',
        ]);

        try {
            $subscription = $this->subscriptionService->changePlan(
                $subscription,
                $validated['plan_id'],
                $validated['immediate'] ?? true
            );

            return response()->json([
                'message' => 'Plan cambiado exitosamente',
                'data' => $subscription,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function addNote(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'is_internal' => 'nullable|boolean',
        ]);

        $this->subscriptionService->addNote(
            $subscription,
            $validated['content'],
            $validated['is_internal'] ?? false
        );

        return response()->json([
            'message' => 'Nota agregada exitosamente',
        ], 201);
    }

    public function workflow(Subscription $subscription): JsonResponse
    {
        $token = $subscription->getActiveWorkflowToken();

        if (!$token) {
            return response()->json([
                'message' => 'No hay un workflow activo para esta suscripción',
            ], 404);
        }

        return response()->json([
            'data' => [
                'token' => $token->load(['currentPlace', 'workflow']),
                'available_transitions' => $subscription->getAvailableTransitions(),
                'history' => $subscription->getWorkflowHistory(),
            ],
        ]);
    }

    public function executeTransition(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'transition' => 'required|string',
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            $token = $subscription->executeTransition(
                $validated['transition'],
                [],
                $validated['comment'] ?? null
            );

            return response()->json([
                'message' => 'Transición ejecutada exitosamente',
                'data' => [
                    'token' => $token->load(['currentPlace', 'workflow']),
                    'available_transitions' => $subscription->getAvailableTransitions(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->subscriptionService->getStats(),
        ]);
    }

    public function enums(): JsonResponse
    {
        return response()->json([
            'data' => [
                'statuses' => SubscriptionStatus::toArray(),
                'billing_cycles' => BillingCycle::toArray(),
            ],
        ]);
    }
}
