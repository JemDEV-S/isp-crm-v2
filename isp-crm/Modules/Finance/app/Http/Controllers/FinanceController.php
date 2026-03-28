<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\Wallet;
use Modules\Finance\Services\InvoiceService;
use Modules\Finance\Services\WalletService;
use Modules\Subscription\Entities\Subscription;

class FinanceController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected InvoiceService $invoiceService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'wallets' => Wallet::count(),
                'invoices' => Invoice::count(),
                'paid_invoices' => Invoice::where('status', 'paid')->count(),
            ],
        ]);
    }

    public function show($id): JsonResponse
    {
        $invoice = Invoice::with(['items', 'customer', 'subscription'])->findOrFail($id);

        return response()->json([
            'data' => $invoice,
        ]);
    }

    public function wallet(Customer $customer): JsonResponse
    {
        return response()->json([
            'data' => $this->walletService->getOrCreateForCustomer($customer->id),
        ]);
    }

    public function generateInitialInvoice(Subscription $subscription): JsonResponse
    {
        $invoice = $this->invoiceService->generateInitialInvoice($subscription->id);

        return response()->json([
            'message' => 'Factura inicial generada',
            'data' => $invoice,
        ], 201);
    }

    public function sendInvoice(Invoice $invoice): JsonResponse
    {
        $this->invoiceService->sendInvoice($invoice->id);

        return response()->json([
            'message' => 'Factura marcada como enviada',
        ]);
    }

    public function payInvoice(Invoice $invoice): JsonResponse
    {
        $invoice = $this->invoiceService->markAsPaid($invoice->id);

        return response()->json([
            'message' => 'Pago registrado',
            'data' => $invoice,
        ]);
    }
}
