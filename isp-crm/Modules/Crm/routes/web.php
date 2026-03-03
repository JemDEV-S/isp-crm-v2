<?php

use Illuminate\Support\Facades\Route;
use Modules\Crm\Http\Controllers\LeadController;
use Modules\Crm\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Web Routes - Módulo CRM
|--------------------------------------------------------------------------
|
| Rutas web para la gestión de Leads (prospectos) y Customers (clientes).
| Incluye operaciones CRUD y acciones específicas del dominio.
|
*/

Route::middleware(['web', 'auth'])->prefix('crm')->name('crm.')->group(function () {

    // ========== LEADS ==========
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::get('/create', [LeadController::class, 'create'])->name('create');
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::get('/{lead}', [LeadController::class, 'show'])->name('show');
        Route::get('/{lead}/edit', [LeadController::class, 'edit'])->name('edit');
        Route::put('/{lead}', [LeadController::class, 'update'])->name('update');
        Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('destroy');

        // Acciones específicas
        Route::post('/{lead}/convert', [LeadController::class, 'convert'])->name('convert');
        Route::patch('/{lead}/status', [LeadController::class, 'changeStatus'])->name('change-status');
        Route::patch('/{lead}/assign', [LeadController::class, 'assign'])->name('assign');
    });

    // ========== CUSTOMERS ==========
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');

        // Acciones específicas
        Route::patch('/{customer}/activate', [CustomerController::class, 'activate'])->name('activate');
        Route::patch('/{customer}/deactivate', [CustomerController::class, 'deactivate'])->name('deactivate');

        // Gestión de direcciones, contactos y notas
        Route::post('/{customer}/addresses', [CustomerController::class, 'addAddress'])->name('addresses.add');
        Route::post('/{customer}/contacts', [CustomerController::class, 'addContact'])->name('contacts.add');
        Route::post('/{customer}/notes', [CustomerController::class, 'addNote'])->name('notes.add');
    });
});
