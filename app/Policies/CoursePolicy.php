<?php

namespace App\Policies;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Enums\AffiliationType;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function __construct(private ActiveAffiliationContext $activeAffiliationContext) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->isGlobalAdministrator($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): bool
    {
        return $this->isGlobalAdministrator($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isGlobalAdministrator($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        return $this->isGlobalAdministrator($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        return $this->isGlobalAdministrator($user) && $course->canBeDeleted();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Course $course): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Course $course): bool
    {
        return false;
    }

    /**
     * Determine whether the course can be made unavailable for new operations.
     */
    public function deactivate(User $user, Course $course): bool
    {
        return $this->isGlobalAdministrator($user) && $course->deactivated_at === null;
    }

    /**
     * Determine whether the course can be returned to operation.
     */
    public function reactivate(User $user, Course $course): bool
    {
        return $this->isGlobalAdministrator($user) && $course->deactivated_at !== null;
    }

    private function isGlobalAdministrator(User $user): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation?->type === AffiliationType::Administrator
            && $affiliation->course_id === null;
    }
}
