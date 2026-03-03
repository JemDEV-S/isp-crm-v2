<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\SubscriptionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('/stats', [SubscriptionController::class, 'stats'])->name('subscriptions.stats');
        Route::get('/enums', [SubscriptionController::class, 'enums'])->name('subscriptions.enums');
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::post('/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('subscriptions.activate');
        Route::post('/{subscription}/suspend', [SubscriptionController::class, 'suspend'])->name('subscriptions.suspend');
        Route::post('/{subscription}/reactivate', [SubscriptionController::class, 'reactivate'])->name('subscriptions.reactivate');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
        Route::post('/{subscription}/notes', [SubscriptionController::class, 'addNote'])->name('subscriptions.add-note');

        // Workflow
        Route::get('/{subscription}/workflow', [SubscriptionController::class, 'workflow'])->name('subscriptions.workflow');
        Route::post('/{subscription}/workflow/transition', [SubscriptionController::class, 'executeTransition'])->name('subscriptions.execute-transition');
    });
});
