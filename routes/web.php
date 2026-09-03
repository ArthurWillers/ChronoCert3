<?php

use App\Http\Controllers\AffiliationSelectionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/affiliations/select', [AffiliationSelectionController::class, 'create'])
        ->name('affiliations.select');
    Route::post('/affiliations/select', [AffiliationSelectionController::class, 'store'])
        ->name('affiliations.select.store');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('active-affiliation')
        ->name('dashboard');

    Route::view('/settings', 'settings')->name('settings');
});
