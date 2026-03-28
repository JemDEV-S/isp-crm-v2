<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('finance/invoices/{id}', [FinanceController::class, 'show'])->name('finance.invoices.show');
    Route::get('finance/customers/{customer}/wallet', [FinanceController::class, 'wallet'])->name('finance.wallet.show');
    Route::post('finance/subscriptions/{subscription}/initial-invoice', [FinanceController::class, 'generateInitialInvoice'])->name('finance.invoices.initial');
    Route::post('finance/invoices/{invoice}/send', [FinanceController::class, 'sendInvoice'])->name('finance.invoices.send');
    Route::post('finance/invoices/{invoice}/pay', [FinanceController::class, 'payInvoice'])->name('finance.invoices.pay');
});
