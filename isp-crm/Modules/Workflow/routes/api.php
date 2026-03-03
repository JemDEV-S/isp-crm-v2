<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Http\Controllers\WorkflowController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('workflow')->group(function () {
        Route::get('/', [WorkflowController::class, 'index'])->name('workflow.index');
        Route::get('/{workflow}', [WorkflowController::class, 'show'])->name('workflow.show');

        Route::prefix('tokens')->group(function () {
            Route::get('/{tokenId}', [WorkflowController::class, 'getToken'])->name('workflow.tokens.show');
            Route::post('/{tokenId}/transition', [WorkflowController::class, 'executeTransition'])->name('workflow.tokens.transition');
            Route::get('/{tokenId}/history', [WorkflowController::class, 'getHistory'])->name('workflow.tokens.history');
            Route::get('/{tokenId}/transitions', [WorkflowController::class, 'getAvailableTransitions'])->name('workflow.tokens.transitions');
        });
    });
});
