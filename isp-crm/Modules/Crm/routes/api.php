<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Crm\Http\Controllers\CustomerController;
use Modules\Crm\Http\Controllers\LeadController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Leads
    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('leads.index');
        Route::post('/', [LeadController::class, 'store'])->name('leads.store');
        Route::get('/stats', [LeadController::class, 'stats'])->name('leads.stats');
        Route::get('/enums', [LeadController::class, 'enums'])->name('leads.enums');
        Route::post('/{lead}/check-duplicates', [LeadController::class, 'checkDuplicates'])->name('leads.check-duplicates');
        Route::post('/{lead}/feasibility', [LeadController::class, 'checkFeasibility'])->name('leads.feasibility');
        Route::post('/{lead}/reserve-capacity', [LeadController::class, 'reserveCapacity'])->name('leads.reserve-capacity');
        Route::get('/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::put('/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
        Route::post('/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
        Route::post('/{lead}/status', [LeadController::class, 'changeStatus'])->name('leads.change-status');
        Route::post('/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    });

    // Customers
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/stats', [CustomerController::class, 'stats'])->name('customers.stats');
        Route::get('/enums', [CustomerController::class, 'enums'])->name('customers.enums');
        Route::get('/search', [CustomerController::class, 'search'])->name('customers.search');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/{customer}/activate', [CustomerController::class, 'activate'])->name('customers.activate');
        Route::post('/{customer}/deactivate', [CustomerController::class, 'deactivate'])->name('customers.deactivate');
        Route::post('/{customer}/addresses', [CustomerController::class, 'addAddress'])->name('customers.add-address');
        Route::post('/{customer}/notes', [CustomerController::class, 'addNote'])->name('customers.add-note');
        Route::post('/{customer}/contacts', [CustomerController::class, 'addContact'])->name('customers.add-contact');
    });
});
