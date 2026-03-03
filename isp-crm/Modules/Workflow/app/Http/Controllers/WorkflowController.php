<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\WorkflowDefinition;
use Modules\Workflow\Services\WorkflowService;

class WorkflowController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    public function index(): JsonResponse
    {
        $workflows = WorkflowDefinition::with(['places' => function ($query) {
            $query->orderBy('order');
        }])
        ->active()
        ->get();

        return response()->json([
            'data' => $workflows,
        ]);
    }

    public function show(WorkflowDefinition $workflow): JsonResponse
    {
        $workflow->load([
            'places' => fn($q) => $q->orderBy('order'),
            'transitions.fromPlace',
            'transitions.toPlace',
            'transitions.roles',
        ]);

        return response()->json([
            'data' => $workflow,
        ]);
    }

    public function getToken(int $tokenId): JsonResponse
    {
        $token = Token::with([
            'workflow',
            'currentPlace',
            'tokenable',
        ])->findOrFail($tokenId);

        $availableTransitions = $this->workflowService->getAvailableTransitions($token);

        return response()->json([
            'data' => [
                'token' => $token,
                'current_place' => $this->workflowService->getCurrentPlaceInfo($token),
                'available_transitions' => $availableTransitions,
            ],
        ]);
    }

    public function executeTransition(Request $request, int $tokenId): JsonResponse
    {
        $request->validate([
            'transition_code' => 'required|string',
            'metadata' => 'nullable|array',
            'comment' => 'nullable|string|max:500',
        ]);

        $token = Token::findOrFail($tokenId);

        $token = $this->workflowService->executeTransition(
            $token,
            $request->input('transition_code'),
            $request->input('metadata', []),
            $request->input('comment')
        );

        return response()->json([
            'message' => 'Transición ejecutada exitosamente',
            'data' => [
                'token' => $token->load(['currentPlace', 'workflow']),
                'current_place' => $this->workflowService->getCurrentPlaceInfo($token),
                'available_transitions' => $this->workflowService->getAvailableTransitions($token),
            ],
        ]);
    }

    public function getHistory(int $tokenId): JsonResponse
    {
        $token = Token::findOrFail($tokenId);
        $history = $this->workflowService->getPlaceHistory($token);

        return response()->json([
            'data' => $history,
        ]);
    }

    public function getAvailableTransitions(int $tokenId): JsonResponse
    {
        $token = Token::findOrFail($tokenId);
        $transitions = $this->workflowService->getAvailableTransitions($token);

        return response()->json([
            'data' => $transitions,
        ]);
    }
}
