<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Reviews\ApproveAccReview;
use App\Actions\Reviews\ClassifyAccReview;
use App\Actions\Reviews\RejectAccReview;
use App\Actions\Reviews\StartAccReview;
use App\Http\Requests\ApproveAccReviewRequest;
use App\Http\Requests\ClassifyAccReviewRequest;
use App\Http\Requests\CompleteAccReviewRequest;
use App\Http\Requests\RejectAccReviewRequest;
use App\Http\Requests\StartAccReviewRequest;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
            ->with('success', 'Análise iniciada. Classifique o documento antes da decisão.');
    }

    public function update(ClassifyAccReviewRequest $request, AccReview $review): RedirectResponse
    {
        $this->classifyAccReview->execute($review, $request->validated(), $this->activeAffiliation($request), $request->user());

        return redirect()->route('submissions.show', $review->acc_submission_id)
            ->with('success', 'Classificação da análise salva.');
    }

    /**
     * Save an acceptance classification and its final decision together, or reject the document with a reason.
     */
    public function complete(CompleteAccReviewRequest $request, AccReview $review): RedirectResponse
    {
        $activeAffiliation = $this->activeAffiliation($request);
        $data = $request->validated();

        if ($data['decision'] === 'reject') {
            $this->rejectAccReview->execute(
                $review,
                $data['rejection_reason'],
                $activeAffiliation,
                $request->user(),
            );

            return redirect()->route('submissions.show', $review->acc_submission_id)
                ->with('success', 'Documento rejeitado. O descarte foi agendado para 30 dias.');
        }

        DB::transaction(function () use ($review, $data, $activeAffiliation, $request): void {
            $classifiedReview = $this->classifyAccReview->execute(
                $review,
                Arr::only($data, [
                    'original_title',
                    'normalized_title',
                    'certificate_hours',
                    'is_area_related',
                    'acc_category_id',
                ]),
                $activeAffiliation,
                $request->user(),
            );

            $this->approveAccReview->execute($classifiedReview, $activeAffiliation, $request->user());
        });

        return redirect()->route('submissions.show', $review->acc_submission_id)
            ->with('success', 'Documento aceito. A carga horária foi incluída no resumo da categoria.');
    }

    public function approve(ApproveAccReviewRequest $request, AccReview $review): RedirectResponse
    {
        $this->approveAccReview->execute($review, $this->activeAffiliation($request), $request->user());

        return redirect()->route('submissions.show', $review->acc_submission_id)
            ->with('success', 'Documento aceito. A carga horária foi incluída no resumo da categoria.');
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
            ->with('success', 'Documento rejeitado. O descarte foi agendado para 30 dias.');
    }

    private function activeAffiliation(Request $request): Affiliation
    {
        $affiliation = $this->activeAffiliationContext->for($request->user());
        abort_if($affiliation === null, 403);

        return $affiliation;
    }
}
