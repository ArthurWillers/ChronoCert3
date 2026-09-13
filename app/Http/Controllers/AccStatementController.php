<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Statements\BuildAccStatement;
use App\Models\AccSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccStatementController extends Controller
{
    public function __construct(
        private ActiveAffiliationContext $activeAffiliationContext,
        private BuildAccStatement $buildAccStatement,
    ) {}

    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', AccSubmission::class);
        $affiliation = $this->activeAffiliationContext->for($request->user());
        abort_if($affiliation === null, 403);

        return view('statements.index', $this->buildAccStatement->execute($affiliation));
    }
}
