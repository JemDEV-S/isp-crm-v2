<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\InvoiceDispute;
use Modules\Finance\Services\InvoiceDisputeService;

class DisputeController extends Controller
{
    public function __construct(
        protected InvoiceDisputeService $disputeService,
    ) {}

    public function store(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'reason_code' => 'required|string|max:30',
            'description' => 'required|string',
        ]);

        $dispute = $this->disputeService->open([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'reason_code' => $validated['reason_code'],
            'description' => $validated['description'],
        ]);

        return response()->json([
            'data' => $dispute,
            'message' => 'Disputa creada exitosamente',
        ], 201);
    }

    public function resolve(Request $request, InvoiceDispute $dispute): JsonResponse
    {
        $validated = $request->validate([
            'resolution' => 'required|string',
            'status' => 'required|string|in:resolved_favor_customer,resolved_favor_company,closed',
        ]);

        $dispute = $this->disputeService->resolve(
            $dispute,
            $validated['resolution'],
            $validated['status'],
        );

        return response()->json([
            'data' => $dispute,
            'message' => 'Disputa resuelta',
        ]);
    }
}
