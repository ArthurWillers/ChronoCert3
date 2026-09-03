<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Render the dashboard that belongs to the selected affiliation type.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $affiliation = $request->user()
            ->affiliations()
            ->valid()
            ->whereKey((int) $request->session()->get('active_affiliation_id', 0))
            ->first();

        if ($affiliation === null) {
            $request->session()->forget('active_affiliation_id');

            return redirect()->route('affiliations.select');
        }

        return view(match ($affiliation->type->value) {
            'administrator' => 'dashboards.administrator',
            'coordinator' => 'dashboards.coordinator',
            default => 'dashboards.student',
        }, [
            'affiliation' => $affiliation,
        ]);
    }
}
