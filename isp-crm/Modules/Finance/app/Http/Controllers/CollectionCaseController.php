<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\CollectionCase;
use Modules\Finance\Services\CollectionCaseService;

class CollectionCaseController extends Controller
{
    public function __construct(
        protected CollectionCaseService $collectionCaseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = CollectionCase::with(['customer', 'subscription', 'assignedToUser'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $cases = $query->paginate($request->input('per_page', 20));

        return response()->json($cases);
    }

    public function assign(Request $request, CollectionCase $collectionCase): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $case = $this->collectionCaseService->assign($collectionCase, $validated['user_id']);

        return response()->json([
            'data' => $case,
            'message' => 'Caso asignado exitosamente',
        ]);
    }
}
