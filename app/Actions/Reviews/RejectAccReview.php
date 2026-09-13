<?php

namespace App\Actions\Reviews;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Enums\SubmissionStatus;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\User;
use App\Notifications\AccSubmissionRejectedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class RejectAccReview
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function execute(
        AccReview $review,
        string $rejectionReason,
        Affiliation $reviewerAffiliation,
        User $causer,
    ): AccReview {
        $review = DB::transaction(function () use ($review, $rejectionReason, $reviewerAffiliation, $causer): AccReview {
            $review = AccReview::query()->lockForUpdate()->findOrFail($review->getKey());
            $submission = AccSubmission::query()
                ->with(['studentAffiliation.user', 'studentAffiliation.course'])
                ->lockForUpdate()
                ->findOrFail($review->acc_submission_id);
            $review->setRelation('submission', $submission);
            $reviewerAffiliation = Affiliation::query()->with(['user', 'course'])->lockForUpdate()->findOrFail($reviewerAffiliation->getKey());

            Gate::forUser($causer)->authorize('reject', $review);

            if ($submission->status !== SubmissionStatus::UnderReview || $review->completed_at !== null) {
                throw ValidationException::withMessages(['review' => 'Esta análise já recebeu uma decisão final.']);
            }

            $completedAt = now();
            $purgeAt = $completedAt->copy()->addDays(30);
            $review->update([
                'reviewer_affiliation_id' => $reviewerAffiliation->getKey(),
                'rejection_reason' => trim($rejectionReason),
                'completed_at' => $completedAt,
            ]);
            $submission->update([
                'status' => SubmissionStatus::Rejected,
                'reviewed_at' => $completedAt,
                'rejected_at' => $completedAt,
                'purge_at' => $purgeAt,
            ]);

            $this->recordActivity->execute(
                event: AuditEvent::SubmissionRejected,
                subject: $submission,
                causer: $causer,
                activeAffiliation: $reviewerAffiliation,
                contextCourseId: $reviewerAffiliation->course_id,
                references: [
                    'course' => $submission->studentAffiliation->course,
                    'student_affiliation' => $submission->studentAffiliation,
                    'reviewer_affiliation' => $reviewerAffiliation,
                    'review' => $review,
                ],
                changes: [
                    'status' => ['old' => SubmissionStatus::UnderReview->value, 'new' => SubmissionStatus::Rejected->value],
                    'rejected_at' => ['old' => null, 'new' => $completedAt->toIso8601String()],
                    'purge_at' => ['old' => null, 'new' => $purgeAt->toIso8601String()],
                ],
                reason: $review->rejection_reason,
            );

            return $review->refresh()->load('submission.studentAffiliation');
        });

        Notification::route('mail', $review->submission->studentAffiliation->email)
            ->notify(new AccSubmissionRejectedNotification(
                $review->acc_submission_id,
                (string) $review->rejection_reason,
            ));

        return $review;
    }
}
