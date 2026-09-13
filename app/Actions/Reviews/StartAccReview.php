<?php

namespace App\Actions\Reviews;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Enums\SubmissionStatus;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\User;
use App\Notifications\AccReviewStartedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class StartAccReview
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function execute(AccSubmission $submission, Affiliation $reviewerAffiliation, User $causer): AccReview
    {
        $review = DB::transaction(function () use ($submission, $reviewerAffiliation, $causer): AccReview {
            $submission = AccSubmission::query()
                ->with(['studentAffiliation.user', 'studentAffiliation.course', 'submittedByAffiliation', 'media'])
                ->lockForUpdate()
                ->findOrFail($submission->getKey());
            $reviewerAffiliation = Affiliation::query()
                ->with(['user', 'course'])
                ->lockForUpdate()
                ->findOrFail($reviewerAffiliation->getKey());

            Gate::forUser($causer)->authorize('create', [AccReview::class, $submission]);

            if ($submission->status !== SubmissionStatus::Submitted || $submission->review()->exists()) {
                throw ValidationException::withMessages([
                    'submission' => 'Este comprovante já teve a análise iniciada.',
                ]);
            }

            $originalFilename = (string) ($submission->getFirstMedia(AccSubmission::EvidenceCollection)?->getCustomProperty('original_filename') ?? '');
            $originalTitle = trim((string) pathinfo($originalFilename, PATHINFO_FILENAME));
            $startedAt = now();
            $review = AccReview::create([
                'acc_submission_id' => $submission->getKey(),
                'reviewer_affiliation_id' => $reviewerAffiliation->getKey(),
                'original_title' => $originalTitle !== '' ? $originalTitle : 'Atividade complementar',
                'started_at' => $startedAt,
            ]);
            $submission->update([
                'status' => SubmissionStatus::UnderReview,
                'review_started_at' => $startedAt,
            ]);

            $references = [
                'course' => $submission->studentAffiliation->course,
                'student_affiliation' => $submission->studentAffiliation,
                'reviewer_affiliation' => $reviewerAffiliation,
                'review' => $review,
            ];
            $this->recordActivity->execute(
                event: AuditEvent::SubmissionReviewStarted,
                subject: $submission,
                causer: $causer,
                activeAffiliation: $reviewerAffiliation,
                contextCourseId: $reviewerAffiliation->course_id,
                references: $references,
                changes: [
                    'status' => ['old' => SubmissionStatus::Submitted->value, 'new' => SubmissionStatus::UnderReview->value],
                    'review_started_at' => ['old' => null, 'new' => $startedAt->toIso8601String()],
                ],
            );
            $this->recordActivity->execute(
                event: AuditEvent::ReviewCreated,
                subject: $review,
                causer: $causer,
                activeAffiliation: $reviewerAffiliation,
                contextCourseId: $reviewerAffiliation->course_id,
                references: $references,
            );

            return $review->load('submission.studentAffiliation');
        });

        Notification::route('mail', $review->submission->studentAffiliation->email)
            ->notify(new AccReviewStartedNotification($review->acc_submission_id));

        return $review;
    }
}
