<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'must_change_password'])->group(function () {
    Route::middleware('permission:record payments|view loans')->group(function () {
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    });

    Route::middleware('permission:record payments')->group(function () {
        Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
    });

    Route::middleware('permission:record payments|view loans')->group(function () {
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::get('payments/{payment}/receipt.pdf', [PaymentController::class, 'receiptPdf'])->name('payments.receipt.pdf');
    });

    Route::middleware('permission:record payments')->group(function () {
        Route::get('payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
    });

    Route::middleware('permission:reverse payments')->group(function () {
        Route::patch('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse');
    });
});
