<?php

use Illuminate\Support\Facades\Route;
use Modules\FieldOps\Http\Controllers\FieldOpsController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('fieldops', FieldOpsController::class)->names('fieldops');
    Route::post('fieldops/work-orders/{workOrder}/validate-location', [FieldOpsController::class, 'validateLocation'])->name('fieldops.validate-location');
    Route::post('fieldops/work-orders/{workOrder}/validate', [FieldOpsController::class, 'validateWorkOrder'])->name('fieldops.validate-work-order');
    Route::post('fieldops/work-orders/{workOrder}/reject', [FieldOpsController::class, 'rejectWorkOrder'])->name('fieldops.reject-work-order');
    Route::post('fieldops/work-orders/{workOrder}/request-correction', [FieldOpsController::class, 'requestCorrection'])->name('fieldops.request-correction');
});
