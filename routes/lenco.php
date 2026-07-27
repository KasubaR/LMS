<?php

use App\Http\Controllers\LencoCollectionController;
use App\Http\Controllers\LencoWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'must_change_password', 'permission:record payments'])->group(function () {
    Route::get('lenco/collections', [LencoCollectionController::class, 'index'])->name('lenco.collections.index');
    Route::post('lenco/collections', [LencoCollectionController::class, 'store'])->name('lenco.collections.store');
    Route::post('lenco/collections/{lencoCollectionRequest}/refresh', [LencoCollectionController::class, 'refreshStatus'])
        ->name('lenco.collections.refresh');
});

// Public endpoint — Lenco calls this from their servers, not the browser.
// Exempted from CSRF verification in bootstrap/app.php.
Route::post('webhooks/lenco', LencoWebhookController::class)->name('webhooks.lenco');
