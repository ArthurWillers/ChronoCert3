<?php

namespace App\Http\Controllers;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Http\Requests\SelectActiveAffiliationRequest;
use App\Models\Affiliation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AffiliationSelectionController extends Controller
{
    public function __construct(private RecordActivity $recordActivity) {}

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
        $affiliation = DB::transaction(function () use ($request): Affiliation {
            $affiliation = $request->user()
                ->affiliations()
                ->valid()
                ->whereKey($request->integer('affiliation_id'))
                ->firstOrFail();

            $previousLastUsedAt = $affiliation->last_used_at?->toIso8601String();
            $affiliation->forceFill(['last_used_at' => now()])->save();

            $this->recordActivity->execute(
                event: AuditEvent::AffiliationSelected,
                subject: $affiliation,
                causer: $request->user(),
                activeAffiliation: $affiliation,
                contextCourseId: $affiliation->getAttribute('course_id'),
                references: [
                    'user' => $request->user(),
                    'selected_affiliation' => $affiliation,
                ],
                changes: [
                    'last_used_at' => [
                        'old' => $previousLastUsedAt,
                        'new' => $affiliation->last_used_at?->toIso8601String(),
                    ],
                ],
            );

            return $affiliation;
        });

        $request->session()->put('active_affiliation_id', $affiliation->getKey());

        return redirect()->route('dashboard');
    }
}
