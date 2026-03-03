<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Catalog\DTOs\CreatePlanDTO;
use Modules\Catalog\DTOs\UpdatePlanDTO;
use Modules\Catalog\Entities\Plan;
use Modules\Catalog\Enums\Technology;
use Modules\Catalog\Http\Requests\StorePlanRequest;
use Modules\Catalog\Http\Requests\UpdatePlanRequest;
use Modules\Catalog\Services\AddonService;
use Modules\Catalog\Services\PlanService;
use Modules\Catalog\Services\PromotionService;

class PlanController extends Controller
{
    public function __construct(
        private readonly PlanService $planService,
        private readonly PromotionService $promotionService,
        private readonly AddonService $addonService,
    ) {
        $this->middleware('permission:catalog.plan.view')->only(['index', 'show']);
        $this->middleware('permission:catalog.plan.create')->only(['create', 'store']);
        $this->middleware('permission:catalog.plan.update')->only(['edit', 'update', 'toggleStatus', 'toggleVisibility']);
        $this->middleware('permission:catalog.plan.delete')->only(['destroy']);
    }

    /**
     * Display a listing of plans.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['technology', 'is_active', 'is_visible', 'search']);
        $plans = $this->planService->paginate(15, $filters);

        if ($request->wantsJson()) {
            return response()->json($plans);
        }

        return view('catalog::plans.index', [
            'plans' => $plans,
            'technologies' => Technology::options(),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create(): View
    {
        return view('catalog::plans.create', [
            'technologies' => Technology::options(),
            'promotions' => $this->promotionService->getValidPromotions(),
            'addons' => $this->addonService->getActiveAddons(),
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(StorePlanRequest $request): RedirectResponse|JsonResponse
    {
        $dto = CreatePlanDTO::fromRequest($request);
        $plan = $this->planService->create($dto);

        if ($request->wantsJson()) {
            return response()->json($plan, 201);
        }

        return redirect()
            ->route('catalog.plans.show', $plan)
            ->with('success', 'Plan creado exitosamente.');
    }

    /**
     * Display the specified plan.
     */
    public function show(Plan $plan, Request $request): View|JsonResponse
    {
        $plan->load(['parameters', 'promotions', 'addons', 'ipPool', 'device']);

        if ($request->wantsJson()) {
            return response()->json($plan);
        }

        return view('catalog::plans.show', [
            'plan' => $plan,
        ]);
    }

    /**
     * Show the form for editing the plan.
     */
    public function edit(Plan $plan): View
    {
        $plan->load(['parameters', 'promotions', 'addons']);

        return view('catalog::plans.edit', [
            'plan' => $plan,
            'technologies' => Technology::options(),
            'promotions' => $this->promotionService->getValidPromotions(),
            'addons' => $this->addonService->getActiveAddons(),
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse|JsonResponse
    {
        $dto = UpdatePlanDTO::fromRequest($request);
        $plan = $this->planService->update($plan, $dto);

        if ($request->wantsJson()) {
            return response()->json($plan);
        }

        return redirect()
            ->route('catalog.plans.show', $plan)
            ->with('success', 'Plan actualizado exitosamente.');
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(Plan $plan, Request $request): RedirectResponse|JsonResponse
    {
        $this->planService->delete($plan);

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()
            ->route('catalog.plans.index')
            ->with('success', 'Plan eliminado exitosamente.');
    }

    /**
     * Toggle plan active status.
     */
    public function toggleStatus(Plan $plan, Request $request): RedirectResponse|JsonResponse
    {
        if ($plan->is_active) {
            $plan = $this->planService->deactivate($plan);
            $message = 'Plan desactivado exitosamente.';
        } else {
            $plan = $this->planService->activate($plan);
            $message = 'Plan activado exitosamente.';
        }

        if ($request->wantsJson()) {
            return response()->json($plan);
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Toggle plan visibility.
     */
    public function toggleVisibility(Plan $plan, Request $request): RedirectResponse|JsonResponse
    {
        $plan = $this->planService->toggleVisibility($plan);
        $message = $plan->is_visible ? 'Plan ahora es visible.' : 'Plan ocultado del catálogo público.';

        if ($request->wantsJson()) {
            return response()->json($plan);
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Duplicate a plan.
     */
    public function duplicate(Plan $plan, Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:30|unique:plans,code',
            'name' => 'required|string|max:100',
        ]);

        $newPlan = $this->planService->duplicate(
            $plan,
            $request->input('code'),
            $request->input('name')
        );

        if ($request->wantsJson()) {
            return response()->json($newPlan, 201);
        }

        return redirect()
            ->route('catalog.plans.edit', $newPlan)
            ->with('success', 'Plan duplicado exitosamente.');
    }

    /**
     * Get public plans (for API).
     */
    public function public(Request $request): JsonResponse
    {
        $plans = $this->planService->getPublicPlans();
        return response()->json($plans);
    }
}
