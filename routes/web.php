<?php

use App\Http\Controllers\AccCategoryController;
use App\Http\Controllers\AccReviewController;
use App\Http\Controllers\AccStatementController;
use App\Http\Controllers\AccSubmissionController;
use App\Http\Controllers\AffiliationSelectionController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentAffiliationImportController;
use App\Http\Controllers\UserAffiliationController;
use App\Http\Controllers\UserController;
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

    Route::middleware('active-affiliation')->group(function (): void {
        Route::get('/acc-statement', AccStatementController::class)->name('statements.index');
        Route::get('/submissions', [AccSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/submissions/download-all', [AccSubmissionController::class, 'downloadAll'])->name('submissions.download-all');
        Route::get('/submissions/create', [AccSubmissionController::class, 'create'])->name('submissions.create');
        Route::post('/submissions', [AccSubmissionController::class, 'store'])->name('submissions.store');
        Route::get('/submissions/students/{studentAffiliation}', [AccSubmissionController::class, 'indexFor'])
            ->name('submissions.students.index');
        Route::get('/submissions/students/{studentAffiliation}/download-all', [AccSubmissionController::class, 'downloadAllFor'])
            ->name('submissions.students.download-all');
        Route::get('/submissions/students/{studentAffiliation}/create', [AccSubmissionController::class, 'createFor'])
            ->name('submissions.students.create');
        Route::post('/submissions/students/{studentAffiliation}', [AccSubmissionController::class, 'storeFor'])
            ->name('submissions.students.store');
        Route::post('/submissions/{submission}/review', [AccReviewController::class, 'store'])
            ->name('submissions.review.store');
        Route::patch('/reviews/{review}', [AccReviewController::class, 'update'])
            ->name('reviews.update');
        Route::post('/reviews/{review}/complete', [AccReviewController::class, 'complete'])
            ->name('reviews.complete');
        Route::post('/reviews/{review}/approve', [AccReviewController::class, 'approve'])
            ->name('reviews.approve');
        Route::post('/reviews/{review}/reject', [AccReviewController::class, 'reject'])
            ->name('reviews.reject');
        Route::get('/submissions/{submission}/document', [AccSubmissionController::class, 'document'])
            ->name('submissions.document');
        Route::get('/submissions/{submission}/download', [AccSubmissionController::class, 'download'])
            ->name('submissions.download');
        Route::get('/submissions/{submission}', [AccSubmissionController::class, 'show'])->name('submissions.show');

        Route::patch('/courses/{course}/deactivate', [CourseController::class, 'deactivate'])
            ->name('courses.deactivate');
        Route::patch('/courses/{course}/reactivate', [CourseController::class, 'reactivate'])
            ->name('courses.reactivate');
        Route::resource('courses', CourseController::class)->except('show');

        Route::patch('/categories/{category}/deactivate', [AccCategoryController::class, 'deactivate'])->name('categories.deactivate');
        Route::patch('/categories/{category}/reactivate', [AccCategoryController::class, 'reactivate'])->name('categories.reactivate');
        Route::resource('categories', AccCategoryController::class);

        Route::get('/users/lookup', [UserController::class, 'lookup'])->name('users.lookup');
        Route::get('/users/import', [StudentAffiliationImportController::class, 'create'])
            ->name('users.import.create');
        Route::get('/users/import/template', [StudentAffiliationImportController::class, 'template'])
            ->name('users.import.template');
        Route::post('/users/import/preview', [StudentAffiliationImportController::class, 'preview'])
            ->name('users.import.preview');
        Route::post('/users/import/confirm', [StudentAffiliationImportController::class, 'confirm'])
            ->name('users.import.confirm');
        Route::get('/users/import/report/{report}', [StudentAffiliationImportController::class, 'report'])
            ->name('users.import.report');
        Route::patch('/users/{user}/identity', [UserController::class, 'updateIdentity'])->name('users.identity.update');
        Route::post('/users/{user}/invitation', [UserController::class, 'sendInvitation'])->name('users.invitation.send');
        Route::get('/users/{user}/affiliations/create', [UserAffiliationController::class, 'create'])
            ->name('users.affiliations.create');
        Route::post('/users/{user}/affiliations', [UserAffiliationController::class, 'store'])
            ->name('users.affiliations.store');
        Route::get('/users/{user}/affiliations/{affiliation}/edit', [UserAffiliationController::class, 'edit'])
            ->name('users.affiliations.edit');
        Route::put('/users/{user}/affiliations/{affiliation}', [UserAffiliationController::class, 'update'])
            ->name('users.affiliations.update');
        Route::patch('/users/{user}/affiliations/{affiliation}/deactivate', [UserAffiliationController::class, 'deactivate'])
            ->name('users.affiliations.deactivate');
        Route::patch('/users/{user}/affiliations/{affiliation}/activate', [UserAffiliationController::class, 'activate'])
            ->name('users.affiliations.activate');
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    });

    Route::view('/settings', 'settings')
        ->middleware('active-affiliation')
        ->name('settings');
});
