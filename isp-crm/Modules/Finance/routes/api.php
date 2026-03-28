<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\AgingController;
use Modules\Finance\Http\Controllers\BillingController;
use Modules\Finance\Http\Controllers\CollectionCaseController;
use Modules\Finance\Http\Controllers\DisputeController;
use Modules\Finance\Http\Controllers\DunningController;
use Modules\Finance\Http\Controllers\FinanceController;
use Modules\Finance\Http\Controllers\InvoiceController;
use Modules\Finance\Http\Controllers\PaymentController;
use Modules\Finance\Http\Controllers\PaymentWebhookController;
use Modules\Finance\Http\Controllers\PromiseToPayController;
use Modules\Finance\Http\Controllers\ReconciliationController;
use Modules\Finance\Http\Controllers\WalletController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Dashboard / resumen
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');

    // Wallet
    Route::get('finance/customers/{customer}/wallet', [FinanceController::class, 'wallet'])->name('finance.wallet.show');

    // Factura inicial
    Route::post('finance/subscriptions/{subscription}/initial-invoice', [FinanceController::class, 'generateInitialInvoice'])->name('finance.invoices.initial');

    // Invoices CRUD
    Route::get('finance/invoices', [InvoiceController::class, 'index'])->name('finance.invoices.index');
    Route::get('finance/invoices/{invoice}', [InvoiceController::class, 'show'])->name('finance.invoices.show');
    Route::post('finance/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('finance.invoices.send');
    Route::post('finance/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('finance.invoices.pay');
    Route::post('finance/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('finance.invoices.cancel');

    // Billing - facturacion recurrente
    Route::post('finance/billing/run', [BillingController::class, 'run'])->name('finance.billing.run');
    Route::post('finance/billing/preview', [BillingController::class, 'preview'])->name('finance.billing.preview');
    Route::get('finance/billing/job-runs', [BillingController::class, 'jobRuns'])->name('finance.billing.job-runs');
    Route::get('finance/billing/job-runs/{jobRun}', [BillingController::class, 'jobRunShow'])->name('finance.billing.job-runs.show');
    Route::get('finance/billing/incidents', [BillingController::class, 'incidents'])->name('finance.billing.incidents');
    Route::post('finance/billing/incidents/{incident}/resolve', [BillingController::class, 'resolveIncident'])->name('finance.billing.incidents.resolve');

    // Dunning - Políticas y ejecuciones
    Route::get('finance/dunning/policies', [DunningController::class, 'policies'])->name('finance.dunning.policies');
    Route::post('finance/dunning/policies', [DunningController::class, 'storePolicy'])->name('finance.dunning.policies.store');
    Route::get('finance/dunning/executions', [DunningController::class, 'executions'])->name('finance.dunning.executions');

    // Aging - Reportes
    Route::get('finance/aging/report', [AgingController::class, 'report'])->name('finance.aging.report');
    Route::get('finance/aging/summary', [AgingController::class, 'summary'])->name('finance.aging.summary');

    // Promesas de pago
    Route::post('finance/promises-to-pay', [PromiseToPayController::class, 'store'])->name('finance.promises.store');
    Route::post('finance/promises-to-pay/{promise}/approve', [PromiseToPayController::class, 'approve'])->name('finance.promises.approve');
    Route::post('finance/promises-to-pay/{promise}/cancel', [PromiseToPayController::class, 'cancel'])->name('finance.promises.cancel');
    Route::post('finance/promises-to-pay/{promise}/extend', [PromiseToPayController::class, 'extend'])->name('finance.promises.extend');

    // Disputas de factura
    Route::post('finance/invoices/{invoice}/dispute', [DisputeController::class, 'store'])->name('finance.disputes.store');
    Route::post('finance/disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('finance.disputes.resolve');

    // Casos de cobranza
    Route::get('finance/collection-cases', [CollectionCaseController::class, 'index'])->name('finance.collection-cases.index');
    Route::post('finance/collection-cases/{collectionCase}/assign', [CollectionCaseController::class, 'assign'])->name('finance.collection-cases.assign');

    // Pagos
    Route::post('finance/payments', [PaymentController::class, 'store'])->name('finance.payments.store');
    Route::get('finance/payments', [PaymentController::class, 'index'])->name('finance.payments.index');
    Route::get('finance/payments/{payment}', [PaymentController::class, 'show'])->name('finance.payments.show');
    Route::post('finance/payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('finance.payments.reverse');
    Route::post('finance/invoices/{invoice}/pay-with-payment', [PaymentController::class, 'payInvoice'])->name('finance.invoices.pay-with-payment');

    // Conciliación
    Route::get('finance/reconciliation/unmatched', [ReconciliationController::class, 'unmatched'])->name('finance.reconciliation.unmatched');
    Route::post('finance/reconciliation/allocate', [ReconciliationController::class, 'allocate'])->name('finance.reconciliation.allocate');
    Route::post('finance/reconciliation/auto', [ReconciliationController::class, 'auto'])->name('finance.reconciliation.auto');

    // Wallet
    Route::get('finance/customers/{customer}/wallet/details', [WalletController::class, 'show'])->name('finance.wallet.details');
    Route::get('finance/customers/{customer}/wallet/transactions', [WalletController::class, 'transactions'])->name('finance.wallet.transactions');
    Route::post('finance/customers/{customer}/wallet/credit', [WalletController::class, 'credit'])->name('finance.wallet.credit');
    Route::post('finance/customers/{customer}/wallet/pay-invoice', [WalletController::class, 'payInvoice'])->name('finance.wallet.pay-invoice');
});

// Webhook endpoint - sin autenticación JWT, valida por firma
Route::prefix('v1')->group(function () {
    Route::post('webhooks/payments/{gateway}', [PaymentWebhookController::class, 'handle'])->name('finance.webhooks.payments');
});
