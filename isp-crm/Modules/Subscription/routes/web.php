<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\SubscriptionWebController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('subscriptions')->name('subscriptions.')->group(function () {
    // Resource routes
    Route::get('/', [SubscriptionWebController::class, 'index'])->name('index');
    Route::get('/create', [SubscriptionWebController::class, 'create'])->name('create');
    Route::post('/', [SubscriptionWebController::class, 'store'])->name('store');
    Route::get('/{subscription}', [SubscriptionWebController::class, 'show'])->name('show');
    Route::get('/{subscription}/edit', [SubscriptionWebController::class, 'edit'])->name('edit');
    Route::put('/{subscription}', [SubscriptionWebController::class, 'update'])->name('update');
    Route::delete('/{subscription}', [SubscriptionWebController::class, 'destroy'])->name('destroy');

    // State management routes
    Route::post('/{subscription}/activate', [SubscriptionWebController::class, 'activate'])->name('activate');
    Route::post('/{subscription}/suspend', [SubscriptionWebController::class, 'suspend'])->name('suspend');
    Route::post('/{subscription}/reactivate', [SubscriptionWebController::class, 'reactivate'])->name('reactivate');
    Route::post('/{subscription}/cancel', [SubscriptionWebController::class, 'cancel'])->name('cancel');
});
