<?php

namespace App\Policies;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Enums\AffiliationType;
use App\Models\AccCategory;
use App\Models\Course;
use App\Models\User;

class AccCategoryPolicy
{
    public function __construct(private ActiveAffiliationContext $activeAffiliationContext) {}

    public function viewAny(User $user): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation?->isActive()
            && $affiliation->course_id !== null
            && in_array($affiliation->type, [AffiliationType::Coordinator, AffiliationType::Student], true);
    }

    public function manageAny(User $user): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation?->isActive() && $affiliation->type === AffiliationType::Coordinator && $affiliation->course_id !== null;
    }

    public function view(User $user, AccCategory $category): bool
    {
        return $this->viewCourse($user, $category->course);
    }

    public function viewCourse(User $user, Course $course): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $this->viewAny($user) && (int) $affiliation->course_id === (int) $course->getKey();
    }

    public function create(User $user, Course $course): bool
    {
        return $this->manageAny($user) && $this->viewCourse($user, $course) && $course->deactivated_at === null;
    }

    public function update(User $user, AccCategory $category): bool
    {
        return $this->create($user, $category->course);
    }

    public function delete(User $user, AccCategory $category): bool
    {
        return $this->manageAny($user) && $this->view($user, $category);
    }

    public function deactivate(User $user, AccCategory $category): bool
    {
        return $this->delete($user, $category) && $category->deactivated_at === null;
    }

    public function reactivate(User $user, AccCategory $category): bool
    {
        return $this->update($user, $category) && $category->deactivated_at !== null;
    }
}
