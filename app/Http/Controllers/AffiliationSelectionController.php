<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelectActiveAffiliationRequest;
use App\Models\Affiliation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliationSelectionController extends Controller
{
    /**
     * Show the operation affiliation picker when more than one is available.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $affiliations = $request->user()
            ->affiliations()
            ->valid()
            ->orderBy('type')
            ->orderBy('starts_at')
            ->get();

        if ($affiliations->count() === 1) {
            $request->session()->put('active_affiliation_id', $affiliations->sole()->getKey());

            return redirect()->route('dashboard');
        }

        return view('affiliations.select', [
            'affiliations' => $affiliations,
        ]);
    }

    /**
     * Store the selected affiliation identifier in the authenticated session.
     */
    public function store(SelectActiveAffiliationRequest $request): RedirectResponse
    {
        $request->session()->put('active_affiliation_id', $request->integer('affiliation_id'));

        return redirect()->route('dashboard');
    }
}
