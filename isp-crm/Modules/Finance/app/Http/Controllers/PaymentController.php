<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\DTOs\RegisterPaymentDTO;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|max:30',
            'channel' => 'required|string|max:30',
            'invoice_id' => 'nullable|exists:invoices,id',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $dto = new RegisterPaymentDTO(
            customerId: (int) $validated['customer_id'],
            amount: (float) $validated['amount'],
            method: $validated['method'],
            channel: $validated['channel'],
            invoiceId: isset($validated['invoice_id']) ? (int) $validated['invoice_id'] : null,
            reference: $validated['reference'] ?? null,
            idempotencyKey: $validated['idempotency_key'] ?? null,
            receivedAt: Carbon::now(),
            processedBy: auth()->id(),
            notes: $validated['notes'] ?? null,
        );

        $payment = $this->paymentService->registerPayment($dto);

        return response()->json([
            'message' => 'Pago registrado exitosamente',
            'data' => $payment->load(['allocations.invoice', 'customer']),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['customer', 'allocations.invoice'])
            ->when($request->input('customer_id'), fn ($q, $v) => $q->where('customer_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('method'), fn ($q, $v) => $q->where('method', $v))
            ->when($request->input('channel'), fn ($q, $v) => $q->where('channel', $v))
            ->when($request->input('reconciliation_status'), fn ($q, $v) => $q->where('reconciliation_status', $v))
            ->when($request->input('date_from'), fn ($q, $v) => $q->where('received_at', '>=', $v))
            ->when($request->input('date_to'), fn ($q, $v) => $q->where('received_at', '<=', $v))
            ->orderByDesc('received_at');

        return response()->json([
            'data' => $query->paginate($request->input('per_page', 15)),
        ]);
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json([
            'data' => $payment->load(['customer', 'allocations.invoice', 'processedByUser']),
        ]);
    }

    public function reverse(Payment $payment, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payment = $this->paymentService->reversePayment($payment, $validated['reason']);

        return response()->json([
            'message' => 'Pago revertido',
            'data' => $payment->load(['allocations.invoice']),
        ]);
    }

    public function payInvoice(int $invoiceId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|max:30',
            'channel' => 'nullable|string|max:30',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $dto = new RegisterPaymentDTO(
            customerId: (int) $validated['customer_id'],
            amount: (float) $validated['amount'],
            method: $validated['method'],
            channel: $validated['channel'] ?? 'office',
            invoiceId: $invoiceId,
            reference: $validated['reference'] ?? null,
            receivedAt: Carbon::now(),
            processedBy: auth()->id(),
            notes: $validated['notes'] ?? null,
        );

        $payment = $this->paymentService->payInvoice($invoiceId, $dto);

        return response()->json([
            'message' => 'Pago de factura registrado',
            'data' => $payment->load(['allocations.invoice', 'customer']),
        ], 201);
    }
}
