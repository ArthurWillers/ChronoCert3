<?php

use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\AuditActivity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('private');
    Notification::fake();
    Carbon::setTestNow('2026-09-13 10:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('a rejected submission remains available after 30 days', function () {
    [$submission, $student, $coordinator] = reviewScenario();
    $submission->addMedia(reviewValidPdf('rejeitado.pdf'))->toMediaCollection(AccSubmission::EvidenceCollection);
    $mediaPath = $submission->getFirstMedia(AccSubmission::EvidenceCollection)->getPathRelativeToRoot();
    $review = startReview($this, $submission, $coordinator);
    reviewActingAs($this, $coordinator)
        ->post(route('reviews.reject', $review), ['rejection_reason' => 'Documento inválido para contabilização.'])
        ->assertSessionHasNoErrors();
    $submissionId = $submission->getKey();
    $submission->update(['purge_at' => now()->addDays(30)]);
    Carbon::setTestNow(now()->addDays(31));

    Artisan::call('acc:purge-rejected-submissions');

    expect(AccSubmission::query()->find($submissionId))->not->toBeNull()
        ->and(AccReview::query()->where('acc_submission_id', $submissionId)->exists())->toBeTrue()
        ->and(AuditActivity::query()->where('event', 'submission.rejected')->where('subject_id', $submissionId)->exists())->toBeTrue()
        ->and(AuditActivity::query()->where('event', 'submission.purged')->where('subject_id', $submissionId)->exists())->toBeFalse();
    Storage::disk('private')->assertExists($mediaPath);
});

test('purge is idempotent and ignores submissions that are not expired rejected records', function () {
    [$submission, $student, $coordinator] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);
    reviewActingAs($this, $coordinator)
        ->post(route('reviews.reject', $review), ['rejection_reason' => 'Documento inválido para contabilização.'])
        ->assertSessionHasNoErrors();

    Artisan::call('acc:purge-rejected-submissions');
    Artisan::call('acc:purge-rejected-submissions');

    expect($submission->fresh())->not->toBeNull()
        ->and(AuditActivity::query()->where('event', 'submission.purged')->exists())->toBeFalse();
});
