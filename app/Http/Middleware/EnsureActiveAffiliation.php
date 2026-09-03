<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAffiliation
{
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
            ->get(['id']);
        $selectedId = $request->session()->get('active_affiliation_id');

        if ($selectedId !== null && $affiliations->contains('id', (int) $selectedId)) {
            return $next($request);
        }

        if ($affiliations->count() === 1) {
            $request->session()->put('active_affiliation_id', $affiliations->sole()->getKey());

            return $next($request);
        }

        if ($affiliations->isEmpty()) {
            $request->session()->forget('active_affiliation_id');

            return $next($request);
        }

        return redirect()->route('affiliations.select');
    }
}
