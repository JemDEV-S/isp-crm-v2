<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\AddonController;
use Modules\Catalog\Http\Controllers\PlanController;
use Modules\Catalog\Http\Controllers\PromotionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::middleware(['auth'])->prefix('catalog')->name('catalog.')->group(function () {
    // Plans
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])->name('create');
        Route::post('/', [PlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [PlanController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{plan}/toggle-visibility', [PlanController::class, 'toggleVisibility'])->name('toggle-visibility');
        Route::post('/{plan}/duplicate', [PlanController::class, 'duplicate'])->name('duplicate');
    });

    // Promotions
    Route::prefix('promotions')->name('promotions.')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::get('/create', [PromotionController::class, 'create'])->name('create');
        Route::post('/', [PromotionController::class, 'store'])->name('store');
        Route::get('/{promotion}', [PromotionController::class, 'show'])->name('show');
        Route::get('/{promotion}/edit', [PromotionController::class, 'edit'])->name('edit');
        Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
        Route::post('/{promotion}/toggle-status', [PromotionController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Addons
    Route::prefix('addons')->name('addons.')->group(function () {
        Route::get('/', [AddonController::class, 'index'])->name('index');
        Route::get('/create', [AddonController::class, 'create'])->name('create');
        Route::post('/', [AddonController::class, 'store'])->name('store');
        Route::get('/{addon}', [AddonController::class, 'show'])->name('show');
        Route::get('/{addon}/edit', [AddonController::class, 'edit'])->name('edit');
        Route::put('/{addon}', [AddonController::class, 'update'])->name('update');
        Route::delete('/{addon}', [AddonController::class, 'destroy'])->name('destroy');
        Route::post('/{addon}/toggle-status', [AddonController::class, 'toggleStatus'])->name('toggle-status');
    });
});
