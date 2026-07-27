<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'must_change_password'])->group(function () {
    Route::middleware('permission:view customers')->group(function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    });

    Route::middleware('permission:create customers')->group(function () {
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    });

    Route::middleware('permission:view customers')->group(function () {
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/timeline', [CustomerController::class, 'timeline'])
            ->name('customers.timeline');
        Route::get('customers/{customer}/documents/{document}', [CustomerController::class, 'downloadDocument'])
            ->name('customers.documents.download');
    });

    Route::middleware('permission:edit customers')->group(function () {
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::post('customers/{customer}/documents', [CustomerController::class, 'storeDocument'])
            ->name('customers.documents.store');
        Route::delete('customers/{customer}/documents/{document}', [CustomerController::class, 'destroyDocument'])
            ->name('customers.documents.destroy');
    });

    Route::middleware('permission:archive customers')->group(function () {
        Route::patch('customers/{customer}/archive', [CustomerController::class, 'archive'])
            ->name('customers.archive');
        Route::patch('customers/{customer}/restore', [CustomerController::class, 'restore'])
            ->name('customers.restore');
    });
});
