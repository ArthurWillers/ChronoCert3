<?php

namespace App\Actions\Reviews;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Enums\SubmissionStatus;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReopenAccReview
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function execute(AccReview $review, Affiliation $reviewerAffiliation, User $causer): void
    {
        DB::transaction(function () use ($review, $reviewerAffiliation, $causer): void {
            $review = AccReview::query()->lockForUpdate()->findOrFail($review->getKey());
            $submission = AccSubmission::query()->with('studentAffiliation.course')
                ->lockForUpdate()->findOrFail($review->acc_submission_id);
            $review->setRelation('submission', $submission);
            $student = Affiliation::query()->lockForUpdate()->findOrFail($submission->student_affiliation_id);
            $reviewerAffiliation = Affiliation::query()->with(['user', 'course'])
                ->lockForUpdate()->findOrFail($reviewerAffiliation->getKey());

            Gate::forUser($causer)->authorize('reopen', $review);

            if ($submission->document_sha256 !== null && AccSubmission::query()
                ->where('student_affiliation_id', $student->getKey())
                ->where('document_sha256', $submission->document_sha256)
                ->whereKeyNot($submission->getKey())
                ->whereIn('status', [SubmissionStatus::Submitted, SubmissionStatus::UnderReview, SubmissionStatus::Approved])
                ->exists()) {
                throw ValidationException::withMessages([
                    'review' => 'Este arquivo já possui outro envio ativo. Analise o envio existente para evitar duplicidade.',
                ]);
            }

            $previousStatus = $submission->status;
            $previousReason = $review->rejection_reason;
            $previousRules = $review->rules_snapshot;
            $review->update([
                'completed_at' => null,
                'rejection_reason' => null,
                'category_snapshot' => null,
                'rules_snapshot' => null,
            ]);
            $submission->update([
                'status' => SubmissionStatus::UnderReview,
                'reviewed_at' => null,
                'rejected_at' => null,
                'purge_at' => null,
            ]);

            $this->recordActivity->execute(
                event: AuditEvent::SubmissionReviewReopened,
                subject: $submission,
                causer: $causer,
                activeAffiliation: $reviewerAffiliation,
                contextCourseId: $reviewerAffiliation->course_id,
                references: [
                    'course' => $submission->studentAffiliation->course,
                    'student_affiliation' => $student,
                    'reviewer_affiliation' => $reviewerAffiliation,
                    'review' => $review,
                ],
                changes: [
                    'status' => ['old' => $previousStatus->value, 'new' => SubmissionStatus::UnderReview->value],
                    'rules_snapshot' => ['old' => $previousRules, 'new' => null],
                ],
                reason: $previousReason,
            );
        });
    }
}
