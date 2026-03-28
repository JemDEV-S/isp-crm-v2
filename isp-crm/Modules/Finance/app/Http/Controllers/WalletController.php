<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Enums\WalletConcept;
use Modules\Finance\Services\WalletService;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
    ) {}

    public function show(int $customerId): JsonResponse
    {
        $wallet = $this->walletService->getOrCreateForCustomer($customerId);

        return response()->json([
            'data' => [
                'wallet' => $wallet,
                'balance' => (float) $wallet->balance,
                'credit_limit' => (float) $wallet->credit_limit,
                'available' => (float) $wallet->balance + (float) $wallet->credit_limit,
            ],
        ]);
    }

    public function transactions(int $customerId, Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'concept', 'date_from', 'date_to', 'per_page']);

        return response()->json([
            'data' => $this->walletService->getTransactions($customerId, $filters),
        ]);
    }

    public function credit(int $customerId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'concept' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $transaction = $this->walletService->credit(
            $customerId,
            (float) $validated['amount'],
            $validated['concept'] ?? WalletConcept::ADJUSTMENT->value,
            $validated['description'] ?? 'Abono manual',
        );

        return response()->json([
            'message' => 'Abono registrado',
            'data' => $transaction,
        ], 201);
    }

    public function payInvoice(int $customerId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        $payment = $this->walletService->payFromWallet($customerId, (int) $validated['invoice_id']);

        if (! $payment) {
            return response()->json([
                'message' => 'No se pudo realizar el pago. Verifique el saldo disponible y la factura.',
            ], 422);
        }

        return response()->json([
            'message' => 'Pago desde wallet registrado',
            'data' => $payment->load(['allocations.invoice']),
        ]);
    }
}
