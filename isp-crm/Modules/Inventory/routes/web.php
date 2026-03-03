<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;
use Modules\Inventory\Http\Controllers\ProductController;
use Modules\Inventory\Http\Controllers\MovementController;
use Modules\Inventory\Http\Controllers\WarehouseController;
use Modules\Inventory\Http\Controllers\StockController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->prefix('inventory')->name('inventory.')->group(function () {

    // Dashboard del módulo Inventory
    Route::get('/', [InventoryController::class, 'index'])->name('index');

    // Products
    Route::resource('products', ProductController::class);

    // Movements
    Route::resource('movements', MovementController::class)->except(['edit', 'update', 'destroy']);
    Route::get('movements/adjustment', [MovementController::class, 'adjustment'])->name('movements.adjustment');
    Route::post('movements/adjustment', [MovementController::class, 'storeAdjustment'])->name('movements.storeAdjustment');
    Route::get('movements/check-stock', [MovementController::class, 'checkStock'])->name('movements.checkStock');

    // Warehouses
    Route::resource('warehouses', WarehouseController::class);

    // Stock
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('stock/low-stock', [StockController::class, 'lowStock'])->name('stock.lowStock');
    Route::get('stock/by-warehouse/{warehouse}', [StockController::class, 'byWarehouse'])->name('stock.byWarehouse');
});
