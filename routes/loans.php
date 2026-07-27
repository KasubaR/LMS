<?php

use App\Http\Controllers\LoanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'must_change_password'])->group(function () {
    Route::middleware('permission:view loans')->group(function () {
        Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    });

    Route::middleware('permission:create loans')->group(function () {
        Route::get('loans/create', [LoanController::class, 'create'])->name('loans.create');
        Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
    });

    Route::middleware('permission:create loans|edit loans')->group(function () {
        Route::post('loans/preview-schedule', [LoanController::class, 'previewSchedule'])
            ->name('loans.preview-schedule');
    });

    Route::middleware('permission:view loans')->group(function () {
        Route::get('loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
        Route::get('loans/{loan}/timeline', [LoanController::class, 'timeline'])->name('loans.timeline');
        Route::get('loans/{loan}/schedule', [LoanController::class, 'schedule'])->name('loans.schedule');
    });

    Route::middleware('permission:record payments')->group(function () {
        Route::post('loans/{loan}/installments/{installment}/mark-paid', [LoanController::class, 'markInstallmentPaid'])
            ->name('loans.installments.mark-paid');
    });

    Route::middleware('permission:edit loans')->group(function () {
        Route::get('loans/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
        Route::put('loans/{loan}', [LoanController::class, 'update'])->name('loans.update');
        Route::patch('loans/{loan}/complete', [LoanController::class, 'complete'])->name('loans.complete');
    });

    Route::middleware('permission:delete loans')->group(function () {
        Route::delete('loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');
        Route::patch('loans/{loan}/default', [LoanController::class, 'markDefaulted'])->name('loans.default');
        Route::patch('loans/{loan}/write-off', [LoanController::class, 'writeOff'])->name('loans.write-off');
    });
});
