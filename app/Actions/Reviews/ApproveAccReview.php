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
use Illuminate\Database\Eloquent\Builder;
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

            if ($review->acc_category_id === null || $review->normalized_title === null || $review->approved_hours === null) {
                throw ValidationException::withMessages([
                    'review' => 'Classifique o comprovante antes de aprová-lo.',
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

            $approvedHours = (float) $review->approved_hours;
            $categoryLimit = (float) $category->max_hours;
            $alreadyApproved = (float) AccReview::query()
                ->where('acc_category_id', $category->getKey())
                ->whereHas('submission', function (Builder $query) use ($studentAffiliation): void {
                    $query->where('student_affiliation_id', $studentAffiliation->getKey())
                        ->where('status', SubmissionStatus::Approved);
                })
                ->sum('approved_hours');
            $availableHours = max(0, $categoryLimit - $alreadyApproved);

            if ($approvedHours <= 0) {
                throw ValidationException::withMessages(['approved_hours' => 'As horas aprovadas devem ser maiores que zero.']);
            }

            if ($approvedHours > $availableHours) {
                throw ValidationException::withMessages([
                    'approved_hours' => 'A categoria possui apenas '.number_format($availableHours, 2, ',', '.').' hora(s) disponível(is). Ajuste as horas antes de aprovar.',
                ]);
            }

            $completedAt = now();
            $review->update([
                'reviewer_affiliation_id' => $reviewerAffiliation->getKey(),
                'category_snapshot' => $category->academicSnapshot(),
                'rules_snapshot' => [
                    'category_max_hours' => number_format($categoryLimit, 2, '.', ''),
                    'already_approved_hours' => number_format($alreadyApproved, 2, '.', ''),
                    'available_hours_before_decision' => number_format($availableHours, 2, '.', ''),
                    'approved_hours' => number_format($approvedHours, 2, '.', ''),
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
                    'approved_hours' => ['old' => null, 'new' => $review->approved_hours],
                    'reviewed_at' => ['old' => null, 'new' => $completedAt->toIso8601String()],
                ],
                reason: $review->classification_justification,
            );

            return $review->refresh()->load('submission.studentAffiliation');
        });

        Notification::route('mail', $review->submission->studentAffiliation->email)
            ->notify(new AccSubmissionApprovedNotification(
                $review->acc_submission_id,
                (string) $review->approved_hours,
            ));

        return $review;
    }
}
