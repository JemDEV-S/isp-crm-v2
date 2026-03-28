<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Services\PaymentAllocationService;

class ReconciliationController extends Controller
{
    public function __construct(
        protected PaymentAllocationService $allocationService,
    ) {}

    public function unmatched(Request $request): JsonResponse
    {
        $filters = $request->only(['customer_id', 'date_from', 'date_to', 'per_page']);

        return response()->json([
            'data' => $this->allocationService->getUnmatchedPayments($filters),
        ]);
    }

    public function allocate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $allocation = $this->allocationService->manualAllocate(
            (int) $validated['payment_id'],
            (int) $validated['invoice_id'],
            (float) $validated['amount'],
        );

        return response()->json([
            'message' => 'Conciliación manual realizada',
            'data' => $allocation->load(['payment', 'invoice']),
        ]);
    }

    public function auto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);

        $payment = \Modules\Finance\Entities\Payment::findOrFail($validated['payment_id']);
        $allocations = $this->allocationService->allocateToOldestInvoices($payment);

        return response()->json([
            'message' => 'Conciliación automática ejecutada',
            'data' => [
                'allocations_count' => count($allocations),
                'remaining_amount' => $payment->fresh()->getRemainingAmount(),
            ],
        ]);
    }
}
