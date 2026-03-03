<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Catalog\DTOs\CreateAddonDTO;
use Modules\Catalog\DTOs\UpdateAddonDTO;
use Modules\Catalog\Entities\Addon;
use Modules\Catalog\Http\Requests\StoreAddonRequest;
use Modules\Catalog\Http\Requests\UpdateAddonRequest;
use Modules\Catalog\Services\AddonService;
use Modules\Catalog\Services\PlanService;

class AddonController extends Controller
{
    public function __construct(
        private readonly AddonService $addonService,
        private readonly PlanService $planService,
    ) {
        $this->middleware('permission:catalog.addon.view')->only(['index', 'show']);
        $this->middleware('permission:catalog.addon.create')->only(['create', 'store']);
        $this->middleware('permission:catalog.addon.update')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('permission:catalog.addon.delete')->only(['destroy']);
    }

    /**
     * Display a listing of addons.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['is_active', 'is_recurring', 'search']);
        $addons = $this->addonService->paginate(15, $filters);

        if ($request->wantsJson()) {
            return response()->json($addons);
        }

        return view('catalog::addons.index', [
            'addons' => $addons,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new addon.
     */
    public function create(): View
    {
        return view('catalog::addons.create', [
            'plans' => $this->planService->getAll(['is_active' => true]),
        ]);
    }

    /**
     * Store a newly created addon.
     */
    public function store(StoreAddonRequest $request): RedirectResponse|JsonResponse
    {
        $dto = CreateAddonDTO::fromRequest($request);
        $addon = $this->addonService->create($dto);

        if ($request->wantsJson()) {
            return response()->json($addon, 201);
        }

        return redirect()
            ->route('catalog.addons.show', $addon)
            ->with('success', 'Addon creado exitosamente.');
    }

    /**
     * Display the specified addon.
     */
    public function show(Addon $addon, Request $request): View|JsonResponse
    {
        $addon->load(['plans']);

        if ($request->wantsJson()) {
            return response()->json($addon);
        }

        return view('catalog::addons.show', [
            'addon' => $addon,
        ]);
    }

    /**
     * Show the form for editing the addon.
     */
    public function edit(Addon $addon): View
    {
        $addon->load(['plans']);

        return view('catalog::addons.edit', [
            'addon' => $addon,
            'plans' => $this->planService->getAll(['is_active' => true]),
        ]);
    }

    /**
     * Update the specified addon.
     */
    public function update(UpdateAddonRequest $request, Addon $addon): RedirectResponse|JsonResponse
    {
        $dto = UpdateAddonDTO::fromRequest($request);
        $addon = $this->addonService->update($addon, $dto);

        if ($request->wantsJson()) {
            return response()->json($addon);
        }

        return redirect()
            ->route('catalog.addons.show', $addon)
            ->with('success', 'Addon actualizado exitosamente.');
    }

    /**
     * Remove the specified addon.
     */
    public function destroy(Addon $addon, Request $request): RedirectResponse|JsonResponse
    {
        $this->addonService->delete($addon);

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()
            ->route('catalog.addons.index')
            ->with('success', 'Addon eliminado exitosamente.');
    }

    /**
     * Toggle addon active status.
     */
    public function toggleStatus(Addon $addon, Request $request): RedirectResponse|JsonResponse
    {
        if ($addon->is_active) {
            $addon = $this->addonService->deactivate($addon);
            $message = 'Addon desactivado exitosamente.';
        } else {
            $addon = $this->addonService->activate($addon);
            $message = 'Addon activado exitosamente.';
        }

        if ($request->wantsJson()) {
            return response()->json($addon);
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Get active addons (for API).
     */
    public function active(Request $request): JsonResponse
    {
        $addons = $this->addonService->getActiveAddons();
        return response()->json($addons);
    }

    /**
     * Get addons for a specific plan.
     */
    public function forPlan(int $planId, Request $request): JsonResponse
    {
        $addons = $this->addonService->getAddonsForPlan($planId);
        return response()->json($addons);
    }
}
