<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Catalog\DTOs\CreatePromotionDTO;
use Modules\Catalog\DTOs\UpdatePromotionDTO;
use Modules\Catalog\Entities\Promotion;
use Modules\Catalog\Enums\AppliesTo;
use Modules\Catalog\Enums\DiscountType;
use Modules\Catalog\Http\Requests\StorePromotionRequest;
use Modules\Catalog\Http\Requests\UpdatePromotionRequest;
use Modules\Catalog\Services\PlanService;
use Modules\Catalog\Services\PromotionService;

class PromotionController extends Controller
{
    public function __construct(
        private readonly PromotionService $promotionService,
        private readonly PlanService $planService,
    ) {
        $this->middleware('permission:catalog.promotion.view')->only(['index', 'show']);
        $this->middleware('permission:catalog.promotion.create')->only(['create', 'store']);
        $this->middleware('permission:catalog.promotion.update')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('permission:catalog.promotion.delete')->only(['destroy']);
    }

    /**
     * Display a listing of promotions.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['is_active', 'valid', 'search']);
        $promotions = $this->promotionService->paginate(15, $filters);

        if ($request->wantsJson()) {
            return response()->json($promotions);
        }

        return view('catalog::promotions.index', [
            'promotions' => $promotions,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new promotion.
     */
    public function create(): View
    {
        return view('catalog::promotions.create', [
            'discountTypes' => DiscountType::options(),
            'appliesToOptions' => AppliesTo::options(),
            'plans' => $this->planService->getAll(['is_active' => true]),
        ]);
    }

    /**
     * Store a newly created promotion.
     */
    public function store(StorePromotionRequest $request): RedirectResponse|JsonResponse
    {
        $dto = CreatePromotionDTO::fromRequest($request);
        $promotion = $this->promotionService->create($dto);

        if ($request->wantsJson()) {
            return response()->json($promotion, 201);
        }

        return redirect()
            ->route('catalog.promotions.show', $promotion)
            ->with('success', 'Promoción creada exitosamente.');
    }

    /**
     * Display the specified promotion.
     */
    public function show(Promotion $promotion, Request $request): View|JsonResponse
    {
        $promotion->load(['plans']);

        if ($request->wantsJson()) {
            return response()->json($promotion);
        }

        return view('catalog::promotions.show', [
            'promotion' => $promotion,
        ]);
    }

    /**
     * Show the form for editing the promotion.
     */
    public function edit(Promotion $promotion): View
    {
        $promotion->load(['plans']);

        return view('catalog::promotions.edit', [
            'promotion' => $promotion,
            'discountTypes' => DiscountType::options(),
            'appliesToOptions' => AppliesTo::options(),
            'plans' => $this->planService->getAll(['is_active' => true]),
        ]);
    }

    /**
     * Update the specified promotion.
     */
    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse|JsonResponse
    {
        $dto = UpdatePromotionDTO::fromRequest($request);
        $promotion = $this->promotionService->update($promotion, $dto);

        if ($request->wantsJson()) {
            return response()->json($promotion);
        }

        return redirect()
            ->route('catalog.promotions.show', $promotion)
            ->with('success', 'Promoción actualizada exitosamente.');
    }

    /**
     * Remove the specified promotion.
     */
    public function destroy(Promotion $promotion, Request $request): RedirectResponse|JsonResponse
    {
        $this->promotionService->delete($promotion);

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()
            ->route('catalog.promotions.index')
            ->with('success', 'Promoción eliminada exitosamente.');
    }

    /**
     * Toggle promotion active status.
     */
    public function toggleStatus(Promotion $promotion, Request $request): RedirectResponse|JsonResponse
    {
        if ($promotion->is_active) {
            $promotion = $this->promotionService->deactivate($promotion);
            $message = 'Promoción desactivada exitosamente.';
        } else {
            $promotion = $this->promotionService->activate($promotion);
            $message = 'Promoción activada exitosamente.';
        }

        if ($request->wantsJson()) {
            return response()->json($promotion);
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Validate a promotion code.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'plan_id' => 'nullable|integer|exists:plans,id',
        ]);

        $promotion = $this->promotionService->validateCode(
            $request->input('code'),
            $request->input('plan_id')
        );

        if (!$promotion) {
            return response()->json([
                'valid' => false,
                'message' => 'Código de promoción inválido o expirado.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'promotion' => $promotion,
        ]);
    }

    /**
     * Get valid promotions (for API).
     */
    public function valid(Request $request): JsonResponse
    {
        $promotions = $this->promotionService->getValidPromotions();
        return response()->json($promotions);
    }
}
