<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Subscription\DTOs\RequestPlanChangeDTO;
use Modules\Subscription\Entities\PlanChangeRequest;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Services\PlanChangeService;

class PlanChangeController extends Controller
{
    public function __construct(
        protected PlanChangeService $planChangeService
    ) {}

    /**
     * Preview del impacto del cambio de plan.
     */
    public function preview(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'new_plan_id' => 'required|exists:plans,id',
        ]);

        try {
            $calculation = $this->planChangeService->preview(
                $subscription->id,
                $validated['new_plan_id'],
            );

            return response()->json([
                'data' => $calculation->toArray(),
            ]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Solicitar cambio de plan.
     */
    public function request(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'new_plan_id' => 'required|exists:plans,id',
            'effective_mode' => 'sometimes|in:immediate,next_cycle,scheduled',
            'scheduled_for' => 'required_if:effective_mode,scheduled|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $dto = new RequestPlanChangeDTO(
                subscriptionId: $subscription->id,
                newPlanId: $validated['new_plan_id'],
                effectiveMode: $validated['effective_mode'] ?? 'immediate',
                scheduledFor: isset($validated['scheduled_for'])
                    ? \Carbon\Carbon::parse($validated['scheduled_for'])
                    : null,
                notes: $validated['notes'] ?? null,
                requestedBy: auth()->id(),
            );

            $planChangeRequest = $this->planChangeService->request($dto);

            return response()->json([
                'message' => 'Solicitud de cambio de plan creada exitosamente.',
                'data' => $planChangeRequest->load(['oldPlan', 'newPlan']),
            ], 201);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Historial de cambios de plan de una suscripción.
     */
    public function history(Subscription $subscription): JsonResponse
    {
        $requests = $subscription->planChangeRequests()
            ->with(['oldPlan', 'newPlan', 'requestedByUser', 'approvedByUser'])
            ->paginate(15);

        return response()->json($requests);
    }

    /**
     * Aprobar solicitud de cambio de plan.
     */
    public function approve(Request $request, PlanChangeRequest $planChangeRequest): JsonResponse
    {
        try {
            $result = $this->planChangeService->approve($planChangeRequest, auth()->id());

            return response()->json([
                'message' => 'Solicitud aprobada exitosamente.',
                'data' => $result->load(['oldPlan', 'newPlan']),
            ]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Rechazar solicitud de cambio de plan.
     */
    public function reject(Request $request, PlanChangeRequest $planChangeRequest): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $result = $this->planChangeService->reject(
                $planChangeRequest,
                $validated['reason'],
                auth()->id(),
            );

            return response()->json([
                'message' => 'Solicitud rechazada.',
                'data' => $result,
            ]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Ejecutar manualmente un cambio de plan aprobado.
     */
    public function execute(PlanChangeRequest $planChangeRequest): JsonResponse
    {
        try {
            $result = $this->planChangeService->execute($planChangeRequest);

            return response()->json([
                'message' => 'Cambio de plan ejecutado.',
                'data' => $result->load(['oldPlan', 'newPlan']),
            ]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancelar solicitud de cambio de plan.
     */
    public function cancel(Request $request, PlanChangeRequest $planChangeRequest): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $result = $this->planChangeService->cancel(
                $planChangeRequest,
                $validated['reason'],
            );

            return response()->json([
                'message' => 'Solicitud cancelada.',
                'data' => $result,
            ]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
