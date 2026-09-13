<?php

namespace App\Policies;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Enums\AffiliationType;
use App\Enums\SubmissionStatus;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\User;

class AccReviewPolicy
{
    public function __construct(private ActiveAffiliationContext $activeAffiliationContext) {}

    public function viewAny(User $user): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation?->isActive()
            && $affiliation->course_id !== null
            && in_array($affiliation->type, [AffiliationType::Student, AffiliationType::Coordinator], true);
    }

    public function view(User $user, AccReview $accReview): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        if ($affiliation === null || ! $affiliation->isActive()) {
            return false;
        }

        $submission = $accReview->submission;

        return match ($affiliation->type) {
            AffiliationType::Student => (int) $submission->student_affiliation_id === (int) $affiliation->getKey(),
            AffiliationType::Coordinator => $affiliation->course_id !== null
                && $submission->studentCourseId() === (int) $affiliation->course_id,
            default => false,
        };
    }

    public function create(User $user, AccSubmission $submission): bool
    {
        return $this->isCoordinatorForSubmission(
            $this->activeAffiliationContext->for($user),
            $submission,
        ) && $submission->status === SubmissionStatus::Submitted
            && ! $submission->review()->exists();
    }

    public function update(User $user, AccReview $accReview): bool
    {
        return $this->isCoordinatorForSubmission(
            $this->activeAffiliationContext->for($user),
            $accReview->submission,
        ) && $accReview->submission->status === SubmissionStatus::UnderReview;
    }

    public function approve(User $user, AccReview $accReview): bool
    {
        return $this->update($user, $accReview);
    }

    public function reject(User $user, AccReview $accReview): bool
    {
        return $this->update($user, $accReview);
    }

    public function delete(User $user, AccReview $accReview): bool
    {
        return false;
    }

    public function restore(User $user, AccReview $accReview): bool
    {
        return false;
    }

    public function forceDelete(User $user, AccReview $accReview): bool
    {
        return false;
    }

    private function isCoordinatorForSubmission(?Affiliation $affiliation, AccSubmission $submission): bool
    {
        return $affiliation !== null
            && $affiliation->isActive()
            && $affiliation->type === AffiliationType::Coordinator
            && $affiliation->course_id !== null
            && $submission->studentCourseId() === (int) $affiliation->course_id;
    }
}
