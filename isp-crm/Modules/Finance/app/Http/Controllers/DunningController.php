<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\DunningExecution;
use Modules\Finance\Entities\DunningPolicy;
use Modules\Finance\Entities\DunningStage;

class DunningController extends Controller
{
    public function policies(): JsonResponse
    {
        $policies = DunningPolicy::with('stages')->get();

        return response()->json(['data' => $policies]);
    }

    public function storePolicy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:dunning_policies,code',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'applies_to' => 'nullable|string|max:30',
            'applies_to_value' => 'nullable|string|max:50',
            'stages' => 'array',
            'stages.*.stage_order' => 'required|integer',
            'stages.*.name' => 'required|string|max:50',
            'stages.*.code' => 'required|string|max:30',
            'stages.*.action_type' => 'required|string|max:30',
            'stages.*.min_days_overdue' => 'required|integer|min:0',
            'stages.*.max_days_overdue' => 'required|integer|min:0',
            'stages.*.channels' => 'required|array',
            'stages.*.template_code' => 'nullable|string|max:50',
            'stages.*.auto_execute' => 'boolean',
            'stages.*.requires_approval' => 'boolean',
        ]);

        $stages = $validated['stages'] ?? [];
        unset($validated['stages']);

        $policy = DunningPolicy::create($validated);

        foreach ($stages as $stageData) {
            $policy->stages()->create($stageData);
        }

        return response()->json([
            'data' => $policy->load('stages'),
            'message' => 'Política de dunning creada exitosamente',
        ], 201);
    }

    public function executions(Request $request): JsonResponse
    {
        $query = DunningExecution::with(['invoice', 'stage', 'customer'])
            ->orderBy('executed_at', 'desc');

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('subscription_id')) {
            $query->where('subscription_id', $request->subscription_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $executions = $query->paginate($request->input('per_page', 20));

        return response()->json($executions);
    }
}
