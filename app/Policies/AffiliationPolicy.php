<?php

namespace App\Policies;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Enums\AffiliationType;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;

class AffiliationPolicy
{
    public function __construct(private ActiveAffiliationContext $activeAffiliationContext) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->isGlobalAdministrator($user) || $this->isCoordinator($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Affiliation $affiliation): bool
    {
        return $this->isGlobalAdministrator($user)
            || $this->isStudentAffiliationInSelectedCourse($user, $affiliation);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Affiliation $affiliation): bool
    {
        if ($this->isGlobalAdministrator($user)) {
            return $affiliation->type === AffiliationType::Coordinator
                || ($affiliation->type === AffiliationType::Administrator && $user->is($affiliation->user));
        }

        if (! $this->isCoordinator($user)) {
            return false;
        }

        $activeAffiliation = $this->activeAffiliationContext->for($user);

        return (
            (int) $user->getKey() !== (int) $affiliation->user_id
            && $this->isStudentAffiliationInSelectedCourse($user, $affiliation)
        ) || (
            (int) $user->getKey() === (int) $affiliation->user_id
            && $affiliation->type === AffiliationType::Coordinator
            && (int) $affiliation->course_id === (int) $activeAffiliation?->course_id
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Affiliation $affiliation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Affiliation $affiliation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Affiliation $affiliation): bool
    {
        return false;
    }

    /**
     * Determine whether a new affiliation may be created for the target user.
     */
    public function createFor(
        User $user,
        User $targetUser,
        AffiliationType $type,
        ?Course $course,
        bool $isNewUser = false,
    ): bool {
        if ($this->isGlobalAdministrator($user)) {
            if ($type === AffiliationType::Administrator) {
                return $course === null;
            }

            return $type === AffiliationType::Coordinator
                && $course?->deactivated_at === null;
        }

        $activeAffiliation = $this->activeAffiliationContext->for($user);

        return $this->isCoordinator($user)
            && ! $user->is($targetUser)
            && $type === AffiliationType::Student
            && $course !== null
            && $course->deactivated_at === null
            && (int) $course->getKey() === (int) $activeAffiliation->course_id;
    }

    /**
     * Determine whether a new institutional identity may receive its first affiliation.
     */
    public function createNew(User $user, AffiliationType $type, ?Course $course): bool
    {
        if ($this->isGlobalAdministrator($user)) {
            return ($type === AffiliationType::Administrator && $course === null)
                || ($type === AffiliationType::Coordinator && $course?->deactivated_at === null);
        }

        $activeAffiliation = $this->activeAffiliationContext->for($user);

        return $this->isCoordinator($user)
            && $type === AffiliationType::Student
            && $course !== null
            && $course->deactivated_at === null
            && (int) $course->getKey() === (int) $activeAffiliation->course_id;
    }

    /**
     * Determine whether the affiliation can be reactivated in the current scope.
     */
    public function activate(User $user, Affiliation $affiliation): bool
    {
        if (! $this->update($user, $affiliation) || $affiliation->deactivated_at === null) {
            return false;
        }

        $affiliation->loadMissing('course');

        return $affiliation->course === null || $affiliation->course->deactivated_at === null;
    }

    /**
     * Determine whether the affiliation can be deactivated in the current scope.
     */
    public function deactivate(User $user, Affiliation $affiliation): bool
    {
        return $this->update($user, $affiliation) && $affiliation->deactivated_at === null;
    }

    private function isGlobalAdministrator(User $user): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation?->type === AffiliationType::Administrator
            && $affiliation->course_id === null;
    }

    private function isCoordinator(User $user): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation?->type === AffiliationType::Coordinator
            && $affiliation->course_id !== null;
    }

    private function isStudentAffiliationInSelectedCourse(User $user, Affiliation $affiliation): bool
    {
        $activeAffiliation = $this->activeAffiliationContext->for($user);

        return $this->isCoordinator($user)
            && $affiliation->type === AffiliationType::Student
            && (int) $affiliation->course_id === (int) $activeAffiliation->course_id;
    }
}
