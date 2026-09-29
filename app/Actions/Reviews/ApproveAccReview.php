<?php

namespace App\Actions\Reviews;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\User;
use App\Notifications\AccSubmissionApprovedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ApproveAccReview
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function execute(AccReview $review, Affiliation $reviewerAffiliation, User $causer): AccReview
    {
        $review = DB::transaction(function () use ($review, $reviewerAffiliation, $causer): AccReview {
            $review = AccReview::query()->lockForUpdate()->findOrFail($review->getKey());
            $submission = AccSubmission::query()
                ->with(['studentAffiliation.user', 'studentAffiliation.course'])
                ->lockForUpdate()
                ->findOrFail($review->acc_submission_id);
            $review->setRelation('submission', $submission);
            $studentAffiliation = Affiliation::query()->lockForUpdate()->findOrFail($submission->student_affiliation_id);
            $reviewerAffiliation = Affiliation::query()->with(['user', 'course'])->lockForUpdate()->findOrFail($reviewerAffiliation->getKey());

            Gate::forUser($causer)->authorize('approve', $review);

            if ($submission->status !== SubmissionStatus::UnderReview || $review->completed_at !== null) {
                throw ValidationException::withMessages(['review' => 'Esta análise já recebeu uma decisão final.']);
            }

            if ($review->acc_category_id === null || $review->normalized_title === null || $review->certificate_hours === null) {
                throw ValidationException::withMessages([
                    'review' => 'Classifique o documento antes de aceitá-lo.',
                ]);
            }

            $category = AccCategory::query()->with('course')->lockForUpdate()->findOrFail($review->acc_category_id);

            if ($category->deactivated_at !== null
                || (int) $category->course_id !== (int) $studentAffiliation->course_id
                || (int) $reviewerAffiliation->course_id !== (int) $studentAffiliation->course_id) {
                throw ValidationException::withMessages([
                    'acc_category_id' => 'A aprovação exige uma categoria ativa do curso em análise.',
                ]);
            }

            $categoryLimit = (float) $category->max_hours;
            $certificateHours = (float) $review->certificate_hours;

            $completedAt = now();
            $review->update([
                'reviewer_affiliation_id' => $reviewerAffiliation->getKey(),
                'category_snapshot' => $category->academicSnapshot(),
                'rules_snapshot' => [
                    'category_max_hours' => number_format($categoryLimit, 2, '.', ''),
                    'certificate_hours' => number_format($certificateHours, 2, '.', ''),
                    'is_area_related' => $review->is_area_related,
                ],
                'rejection_reason' => null,
                'completed_at' => $completedAt,
            ]);
            $submission->update([
                'status' => SubmissionStatus::Approved,
                'reviewed_at' => $completedAt,
                'rejected_at' => null,
                'purge_at' => null,
            ]);

            $this->recordActivity->execute(
                event: AuditEvent::SubmissionApproved,
                subject: $submission,
                causer: $causer,
                activeAffiliation: $reviewerAffiliation,
                contextCourseId: $reviewerAffiliation->course_id,
                references: [
                    'course' => $submission->studentAffiliation->course,
                    'student_affiliation' => $submission->studentAffiliation,
                    'reviewer_affiliation' => $reviewerAffiliation,
                    'category' => $category,
                    'review' => $review,
                ],
                changes: [
                    'status' => ['old' => SubmissionStatus::UnderReview->value, 'new' => SubmissionStatus::Approved->value],
                    'certificate_hours' => ['old' => null, 'new' => $review->certificate_hours],
                    'is_area_related' => ['old' => null, 'new' => $review->is_area_related],
                    'reviewed_at' => ['old' => null, 'new' => $completedAt->toIso8601String()],
                ],
            );

            return $review->refresh()->load('submission.studentAffiliation');
        });

        Notification::route('mail', $review->submission->studentAffiliation->email)
            ->notify(new AccSubmissionApprovedNotification(
                $review->acc_submission_id,
            ));

        return $review;
    }
}
