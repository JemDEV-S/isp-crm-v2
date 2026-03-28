<?php

namespace Modules\FieldOps\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\FieldOps\app\Models\TechnicianLocation;
use Modules\FieldOps\app\Models\WorkOrder;
use Modules\FieldOps\app\Services\GeofenceService;
use Modules\FieldOps\app\Services\ValidationService;

class FieldOpsController extends Controller
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected ValidationService $validationService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('fieldops::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('fieldops::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $workOrder = WorkOrder::with([
            'photos',
            'checklistResponse',
            'technicianLocations',
            'validation',
            'exceptions',
        ])->findOrFail($id);

        return response()->json([
            'data' => $workOrder,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('fieldops::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $workOrder = WorkOrder::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);

        $workOrder->update($validated);

        return response()->json([
            'message' => 'Orden actualizada',
            'data' => $workOrder->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $workOrder = WorkOrder::findOrFail($id);
        $workOrder->delete();

        return response()->json([
            'message' => 'Orden eliminada',
        ]);
    }

    public function validateLocation(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric|min:0',
            'radius_meters' => 'nullable|integer|min:10|max:1000',
        ]);

        $location = TechnicianLocation::create([
            'user_id' => auth()->id(),
            'work_order_id' => $workOrder->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
            'recorded_at' => now(),
        ]);

        $result = $this->geofenceService->validateWorkOrderArrival(
            $workOrder,
            $location,
            (int) ($validated['radius_meters'] ?? 100),
        );

        return response()->json([
            'message' => 'Ubicacion validada',
            'data' => $result,
        ]);
    }

    public function validateWorkOrder(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $validated = $request->validate([
            'required' => 'nullable|array',
            'required.*' => 'string',
        ]);

        $result = $this->validationService->approve(
            $workOrder->id,
            $validated,
            auth()->id(),
        );

        $statusCode = $result->passed ? 200 : 422;

        return response()->json([
            'message' => $result->passed ? 'Orden validada' : 'Orden rechazada por validacion',
            'data' => $result->toArray(),
        ], $statusCode);
    }

    public function rejectWorkOrder(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $validated = $request->validate([
            'observations' => 'required|array|min:1',
        ]);

        $this->validationService->reject($workOrder->id, $validated['observations']);

        return response()->json([
            'message' => 'Orden rechazada',
        ]);
    }

    public function requestCorrection(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $validated = $request->validate([
            'issues' => 'required|array|min:1',
        ]);

        $this->validationService->requestCorrection($workOrder->id, $validated['issues']);

        return response()->json([
            'message' => 'Correccion solicitada',
        ]);
    }
}
