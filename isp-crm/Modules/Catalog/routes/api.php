<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\AddonController;
use Modules\Catalog\Http\Controllers\PlanController;
use Modules\Catalog\Http\Controllers\PromotionController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group.
 *
*/

Route::prefix('v1/catalog')->name('api.catalog.')->group(function () {
    // Public routes (no authentication required)
    Route::get('/plans/public', [PlanController::class, 'public'])->name('plans.public');
    Route::get('/promotions/valid', [PromotionController::class, 'valid'])->name('promotions.valid');
    Route::post('/promotions/validate-code', [PromotionController::class, 'validateCode'])->name('promotions.validate-code');

    // Authenticated routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Plans
        Route::apiResource('plans', PlanController::class);
        Route::post('/plans/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('plans.toggle-status');
        Route::post('/plans/{plan}/toggle-visibility', [PlanController::class, 'toggleVisibility'])->name('plans.toggle-visibility');
        Route::post('/plans/{plan}/duplicate', [PlanController::class, 'duplicate'])->name('plans.duplicate');

        // Promotions
        Route::apiResource('promotions', PromotionController::class);
        Route::post('/promotions/{promotion}/toggle-status', [PromotionController::class, 'toggleStatus'])->name('promotions.toggle-status');

        // Addons
        Route::apiResource('addons', AddonController::class);
        Route::post('/addons/{addon}/toggle-status', [AddonController::class, 'toggleStatus'])->name('addons.toggle-status');
        Route::get('/addons/active', [AddonController::class, 'active'])->name('addons.active');
        Route::get('/addons/for-plan/{planId}', [AddonController::class, 'forPlan'])->name('addons.for-plan');
    });
});
