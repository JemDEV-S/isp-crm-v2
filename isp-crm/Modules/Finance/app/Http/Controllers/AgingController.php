<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Services\AgingService;

class AgingController extends Controller
{
    public function __construct(
        protected AgingService $agingService,
    ) {}

    public function report(Request $request): JsonResponse
    {
        $filters = $request->only(['customer_id']);

        return response()->json([
            'data' => $this->agingService->getAgingReport($filters),
        ]);
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => $this->agingService->getAgingSummary(),
        ]);
    }
}
