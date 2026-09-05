<?php

namespace App\Actions\Users;

use App\Jobs\SendUserInvitationJob;
use App\Models\Affiliation;
use App\Models\User;

class SendUserInvitation
{
    /**
     * Queue an access invitation after the surrounding transaction is committed.
     */
    public function execute(User $user, User $causer, Affiliation $activeAffiliation): void
    {
        SendUserInvitationJob::dispatch(
            userId: $user->getKey(),
            causerId: $causer->getKey(),
            activeAffiliationId: $activeAffiliation->getKey(),
        )->afterCommit();
    }
}
