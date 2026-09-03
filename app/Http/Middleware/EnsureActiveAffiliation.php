<?php

namespace App\Http\Middleware;

use App\Actions\Affiliations\ActiveAffiliationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAffiliation
{
    public function __construct(private ActiveAffiliationContext $activeAffiliationContext) {}

    /**
     * Select the only valid affiliation or send the user to the picker.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $affiliations = $request->user()
            ->affiliations()
            ->valid()
            ->get();
        $selectedId = $request->session()->get('active_affiliation_id');

        if ($selectedId !== null && $affiliations->contains('id', (int) $selectedId)) {
            $this->activeAffiliationContext->remember($affiliations->firstWhere('id', (int) $selectedId));

            return $next($request);
        }

        if ($affiliations->count() === 1) {
            $affiliation = $affiliations->sole();
            $request->session()->put('active_affiliation_id', $affiliation->getKey());
            $this->activeAffiliationContext->remember($affiliation);

            return $next($request);
        }

        if ($affiliations->isEmpty()) {
            $request->session()->forget('active_affiliation_id');
            $this->activeAffiliationContext->forget();

            return $next($request);
        }

        return redirect()->route('affiliations.select');
    }
}
