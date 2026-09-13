<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Reviews\ApproveAccReview;
use App\Actions\Reviews\ClassifyAccReview;
use App\Actions\Reviews\RejectAccReview;
use App\Actions\Reviews\StartAccReview;
use App\Http\Requests\ApproveAccReviewRequest;
use App\Http\Requests\ClassifyAccReviewRequest;
use App\Http\Requests\RejectAccReviewRequest;
use App\Http\Requests\StartAccReviewRequest;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccReviewController extends Controller
{
    public function __construct(
        private ActiveAffiliationContext $activeAffiliationContext,
        private StartAccReview $startAccReview,
        private ClassifyAccReview $classifyAccReview,
        private ApproveAccReview $approveAccReview,
        private RejectAccReview $rejectAccReview,
    ) {}

    public function store(StartAccReviewRequest $request, AccSubmission $submission): RedirectResponse
    {
        $this->startAccReview->execute($submission, $this->activeAffiliation($request), $request->user());

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Análise iniciada. Classifique o comprovante antes da decisão.');
    }

    public function update(ClassifyAccReviewRequest $request, AccReview $review): RedirectResponse
    {
        $this->classifyAccReview->execute($review, $request->validated(), $this->activeAffiliation($request), $request->user());

        return redirect()->route('submissions.show', $review->acc_submission_id)
            ->with('success', 'Classificação da análise salva.');
    }

    public function approve(ApproveAccReviewRequest $request, AccReview $review): RedirectResponse
    {
        $this->approveAccReview->execute($review, $this->activeAffiliation($request), $request->user());

        return redirect()->route('submissions.show', $review->acc_submission_id)
            ->with('success', 'Comprovante aprovado e horas contabilizadas.');
    }

    public function reject(RejectAccReviewRequest $request, AccReview $review): RedirectResponse
    {
        $this->rejectAccReview->execute(
            $review,
            $request->validated('rejection_reason'),
            $this->activeAffiliation($request),
            $request->user(),
        );

        return redirect()->route('submissions.show', $review->acc_submission_id)
            ->with('success', 'Comprovante rejeitado. O descarte foi agendado para 30 dias.');
    }

    private function activeAffiliation(Request $request): Affiliation
    {
        $affiliation = $this->activeAffiliationContext->for($request->user());
        abort_if($affiliation === null, 403);

        return $affiliation;
    }
}
