<?php

namespace App\Policies;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Enums\AffiliationType;
use App\Models\AuditActivity;
use App\Models\User;

class AuditActivityPolicy
{
    public function __construct(private ActiveAffiliationContext $activeAffiliationContext) {}

    /**
     * Determine whether the user can view the institutional audit trail.
     */
    public function viewAny(User $user): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        if ($affiliation === null) {
            return false;
        }

        return (
            $affiliation->type === AffiliationType::Administrator
            && $affiliation->getAttribute('course_id') === null
        ) || (
            $affiliation->type === AffiliationType::Coordinator
            && $affiliation->getAttribute('course_id') !== null
        );
    }

    /**
     * Determine whether the user can view the selected activity.
     */
    public function view(User $user, AuditActivity $auditActivity): bool
    {
        $affiliation = $this->activeAffiliationContext->for($user);

        return $affiliation !== null
            && $this->viewAny($user)
            && $auditActivity->isVisibleTo($affiliation);
    }
}
