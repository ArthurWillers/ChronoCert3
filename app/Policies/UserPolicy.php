<?php

namespace App\Policies;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Enums\AffiliationType;
use App\Models\User;

class UserPolicy
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
    public function view(User $user, User $model): bool
    {
        if ($this->isGlobalAdministrator($user)) {
            return true;
        }

        $affiliation = $this->activeAffiliationContext->for($user);

        return $this->isCoordinator($user)
            && $model->hasStudentAffiliationForCourse((int) $affiliation->course_id);
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
    public function update(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return ! $user->is($model)
            && $this->isGlobalAdministrator($user)
            && ! $model->affiliations()->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * A selected global administrator may edit institutional identities.
     */
    public function updateIdentity(User $user, User $model): bool
    {
        return $this->isGlobalAdministrator($user);
    }

    /**
     * Resolve a user by CPF when a role can create an affiliation for them.
     */
    public function findByCpf(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the actor may begin adding an affiliation to the target.
     */
    public function addAffiliation(User $user, User $model): bool
    {
        return $this->isGlobalAdministrator($user)
            || ($this->isCoordinator($user) && ! $user->is($model));
    }

    /**
     * Determine whether the actor may send an access invitation to the target.
     */
    public function sendInvitation(User $user, User $model): bool
    {
        return $this->view($user, $model);
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
}
