<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Services\InvoiceService;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer', 'subscription'])
            ->when($request->input('customer_id'), fn($q, $v) => $q->where('customer_id', $v))
            ->when($request->input('subscription_id'), fn($q, $v) => $q->where('subscription_id', $v))
            ->when($request->input('status'), fn($q, $v) => $q->where('status', $v))
            ->when($request->input('type'), fn($q, $v) => $q->where('type', $v))
            ->when($request->input('billing_period'), fn($q, $v) => $q->where('billing_period', $v))
            ->orderByDesc('created_at');

        return response()->json([
            'data' => $query->paginate($request->input('per_page', 15)),
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json([
            'data' => $invoice->load(['items', 'customer', 'subscription', 'billingJobRun']),
        ]);
    }

    public function send(Invoice $invoice): JsonResponse
    {
        $this->invoiceService->sendInvoice($invoice->id);

        return response()->json([
            'message' => 'Factura enviada',
        ]);
    }

    public function pay(Invoice $invoice): JsonResponse
    {
        $invoice = $this->invoiceService->markAsPaid($invoice->id);

        return response()->json([
            'message' => 'Pago registrado',
            'data' => $invoice,
        ]);
    }

    public function cancel(Invoice $invoice): JsonResponse
    {
        if ($invoice->status === InvoiceStatus::CANCELLED) {
            return response()->json(['message' => 'La factura ya esta anulada'], 422);
        }

        if ($invoice->status === InvoiceStatus::PAID) {
            return response()->json(['message' => 'No se puede anular una factura pagada'], 422);
        }

        $invoice->update(['status' => InvoiceStatus::CANCELLED]);

        return response()->json([
            'message' => 'Factura anulada',
            'data' => $invoice->fresh(),
        ]);
    }
}
