<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\PlanChangeController;
use Modules\Subscription\Http\Controllers\SubscriptionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('/stats', [SubscriptionController::class, 'stats'])->name('subscriptions.stats');
        Route::get('/enums', [SubscriptionController::class, 'enums'])->name('subscriptions.enums');
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::get('/{subscription}/activation-readiness', [SubscriptionController::class, 'activationReadiness'])->name('subscriptions.activation-readiness');
        Route::post('/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('subscriptions.activate');
        Route::post('/{subscription}/suspend', [SubscriptionController::class, 'suspend'])->name('subscriptions.suspend');
        Route::post('/{subscription}/reactivate', [SubscriptionController::class, 'reactivate'])->name('subscriptions.reactivate');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
        Route::post('/{subscription}/notes', [SubscriptionController::class, 'addNote'])->name('subscriptions.add-note');
        Route::post('/{subscription}/documents', [SubscriptionController::class, 'addDocument'])->name('subscriptions.add-document');
        Route::post('/{subscription}/documents/validate', [SubscriptionController::class, 'validateDocuments'])->name('subscriptions.validate-documents');
        Route::post('/{subscription}/accept-terms', [SubscriptionController::class, 'acceptTerms'])->name('subscriptions.accept-terms');

        // Plan Change
        Route::post('/{subscription}/plan-change/preview', [PlanChangeController::class, 'preview'])->name('subscriptions.plan-change.preview');
        Route::post('/{subscription}/plan-change/request', [PlanChangeController::class, 'request'])->name('subscriptions.plan-change.request');
        Route::get('/{subscription}/plan-change/history', [PlanChangeController::class, 'history'])->name('subscriptions.plan-change.history');

        // Workflow
        Route::get('/{subscription}/workflow', [SubscriptionController::class, 'workflow'])->name('subscriptions.workflow');
        Route::post('/{subscription}/workflow/transition', [SubscriptionController::class, 'executeTransition'])->name('subscriptions.execute-transition');
    });

    // Plan Change Request actions
    Route::prefix('plan-changes')->group(function () {
        Route::post('/{planChangeRequest}/approve', [PlanChangeController::class, 'approve'])->name('plan-changes.approve');
        Route::post('/{planChangeRequest}/reject', [PlanChangeController::class, 'reject'])->name('plan-changes.reject');
        Route::post('/{planChangeRequest}/execute', [PlanChangeController::class, 'execute'])->name('plan-changes.execute');
        Route::post('/{planChangeRequest}/cancel', [PlanChangeController::class, 'cancel'])->name('plan-changes.cancel');
    });
});
