<?php

use App\Http\Controllers\AffiliationSelectionController;
use App\Http\Controllers\AuditController;
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

    Route::get('/audit', [AuditController::class, 'index'])
        ->middleware('active-affiliation')
        ->name('audit.index');
    Route::get('/audit/{auditActivity}', [AuditController::class, 'show'])
        ->middleware('active-affiliation')
        ->name('audit.show');

    Route::view('/settings', 'settings')
        ->middleware('active-affiliation')
        ->name('settings');
});
