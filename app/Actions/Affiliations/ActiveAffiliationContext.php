<?php

namespace App\Actions\Affiliations;

use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Http\Request;

class ActiveAffiliationContext
{
    public const RequestAttribute = 'chronocert.active_affiliation';

    /**
     * Create the context resolver for the current request.
     */
    public function __construct(private Request $request) {}

    /**
     * Resolve the authenticated user's valid affiliation selected for this request.
     */
    public function for(User $user): ?Affiliation
    {
        $resolvedAffiliation = $this->request->attributes->get(self::RequestAttribute);

        if (
            $resolvedAffiliation instanceof Affiliation
            && (int) $resolvedAffiliation->user_id === (int) $user->getKey()
        ) {
            return $resolvedAffiliation;
        }

        $affiliationId = $this->request->session()->get('active_affiliation_id');

        if (! is_numeric($affiliationId)) {
            $affiliations = $user->affiliations()->valid()->get();

            if ($affiliations->count() !== 1) {
                return null;
            }

            $affiliation = $affiliations->first();

            if ($affiliation !== null) {
                $this->remember($affiliation);
                $this->request->session()->put('active_affiliation_id', $affiliation->getKey());
            }

            return $affiliation;
        }

        if ((int) $affiliationId <= 0) {
            return null;
        }

        $affiliation = $user->affiliations()
            ->valid()
            ->whereKey((int) $affiliationId)
            ->first();

        if ($affiliation !== null) {
            $this->remember($affiliation);
        }

        return $affiliation;
    }

    /**
     * Make an already validated affiliation available for the current request.
     */
    public function remember(Affiliation $affiliation): void
    {
        $this->request->attributes->set(self::RequestAttribute, $affiliation);
    }

    /**
     * Forget an affiliation that is no longer valid for the request.
     */
    public function forget(): void
    {
        $this->request->attributes->remove(self::RequestAttribute);
    }
}
