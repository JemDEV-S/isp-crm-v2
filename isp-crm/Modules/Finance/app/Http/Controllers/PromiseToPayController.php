<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\PromiseToPay;
use Modules\Finance\Services\PromiseToPayService;

class PromiseToPayController extends Controller
{
    public function __construct(
        protected PromiseToPayService $promiseService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'promised_amount' => 'required|numeric|min:0.01',
            'promise_date' => 'required|date|after_or_equal:today',
            'source_channel' => 'required|string|max:30',
            'notes' => 'nullable|string',
        ]);

        $promise = $this->promiseService->create($validated);

        return response()->json([
            'data' => $promise,
            'message' => 'Promesa de pago creada exitosamente',
        ], 201);
    }

    public function approve(PromiseToPay $promise): JsonResponse
    {
        $promise = $this->promiseService->approve($promise, auth()->id());

        return response()->json([
            'data' => $promise,
            'message' => 'Promesa de pago aprobada',
        ]);
    }

    public function cancel(Request $request, PromiseToPay $promise): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $promise = $this->promiseService->cancel($promise, $validated['reason']);

        return response()->json([
            'data' => $promise,
            'message' => 'Promesa de pago cancelada',
        ]);
    }

    public function extend(Request $request, PromiseToPay $promise): JsonResponse
    {
        $validated = $request->validate([
            'new_date' => 'required|date|after:today',
        ]);

        $promise = $this->promiseService->extend($promise, Carbon::parse($validated['new_date']));

        return response()->json([
            'data' => $promise,
            'message' => 'Promesa de pago extendida',
        ]);
    }
}
