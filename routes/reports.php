<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'must_change_password'])->group(function () {
    Route::middleware('permission:view reports')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
        Route::get('reports/loans', [ReportController::class, 'loans'])->name('reports.loans');
        Route::get('reports/outstanding', [ReportController::class, 'outstanding'])->name('reports.outstanding');
        Route::get('reports/collections', [ReportController::class, 'collections'])->name('reports.collections');
        Route::get('reports/interest', [ReportController::class, 'interest'])->name('reports.interest');
        Route::get('reports/overdue', [ReportController::class, 'overdue'])->name('reports.overdue');
        Route::get('reports/officer-performance', [ReportController::class, 'officerPerformance'])->name('reports.officer-performance');
        Route::get('reports/daily-collection', [ReportController::class, 'dailyCollection'])->name('reports.daily-collection');
        Route::get('reports/monthly-summary', [ReportController::class, 'monthlySummary'])->name('reports.monthly-summary');
    });
});
