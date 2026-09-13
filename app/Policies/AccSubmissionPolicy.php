<?php

namespace App\Policies;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Enums\AffiliationType;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;

class AccSubmissionPolicy
{
    public function __construct(private ActiveAffiliationContext $activeAffiliationContext) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation !== null
            && $affiliation->isActive()
            && $affiliation->course_id !== null
            && in_array($affiliation->type, [AffiliationType::Student, AffiliationType::Coordinator], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AccSubmission $accSubmission): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        if ($affiliation === null || ! $affiliation->isActive()) {
            return false;
        }

        if ($affiliation->type === AffiliationType::Student) {
            return (int) $accSubmission->student_affiliation_id === (int) $affiliation->getKey();
        }

        return $affiliation->type === AffiliationType::Coordinator
            && $affiliation->course_id !== null
            && $accSubmission->studentCourseId() === (int) $affiliation->course_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation !== null
            && $this->isActiveStudentAffiliation($affiliation);
    }

    /**
     * Determine whether the coordinator can submit evidence for the selected student affiliation.
     */
    public function createFor(User $user, Affiliation $studentAffiliation): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation !== null
            && $this->isActiveCoordinatorAffiliation($affiliation)
            && $this->isActiveStudentAffiliation($studentAffiliation)
            && (int) $affiliation->course_id === (int) $studentAffiliation->course_id;
    }

    /**
     * Determine whether the user can receive a protected inline response for the proof file.
     */
    public function viewDocument(User $user, AccSubmission $accSubmission): bool
    {
        return $this->view($user, $accSubmission);
    }

    /**
     * Determine whether the user can download the proof file.
     */
    public function download(User $user, AccSubmission $accSubmission): bool
    {
        return $this->view($user, $accSubmission);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AccSubmission $accSubmission): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AccSubmission $accSubmission): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AccSubmission $accSubmission): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AccSubmission $accSubmission): bool
    {
        return false;
    }

    private function isActiveStudentAffiliation(Affiliation $affiliation): bool
    {
        return $affiliation->isActive()
            && $affiliation->type === AffiliationType::Student
            && $affiliation->course_id !== null
            && Course::query()->active()->whereKey($affiliation->course_id)->exists();
    }

    private function isActiveCoordinatorAffiliation(Affiliation $affiliation): bool
    {
        return $affiliation->isActive()
            && $affiliation->type === AffiliationType::Coordinator
            && $affiliation->course_id !== null
            && Course::query()->active()->whereKey($affiliation->course_id)->exists();
    }
}
