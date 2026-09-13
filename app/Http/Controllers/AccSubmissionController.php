<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Audit\RecordActivity;
use App\Actions\Submissions\CreateSubmission;
use App\Enums\AffiliationType;
use App\Enums\AuditEvent;
use App\Enums\SubmissionOrigin;
use App\Http\Requests\IndexAccSubmissionRequest;
use App\Http\Requests\StoreAccSubmissionRequest;
use App\Http\Requests\StoreCoordinatorAccSubmissionRequest;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccSubmissionController extends Controller
{
    public function __construct(
        private ActiveAffiliationContext $activeAffiliationContext,
        private CreateSubmission $createSubmission,
        private RecordActivity $recordActivity,
    ) {}

    /**
     * Display submissions in the scope allowed by the selected affiliation.
     */
    public function index(IndexAccSubmissionRequest $request): View
    {
        $affiliation = $this->activeAffiliation($request);
        $submissions = AccSubmission::query()
            ->visibleTo($affiliation)
            ->with([
                'studentAffiliation.user:id,name',
                'submittedByAffiliation.user:id,name',
                'media',
            ])
            ->when(
                $request->filled('status'),
                fn (Builder $query): Builder => $query->where('status', $request->validated('status')),
            )
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
        $canCreateOwn = $request->user()->can('create', AccSubmission::class);
        $canRegisterForStudents = $affiliation->type === AffiliationType::Coordinator
            && Course::query()->active()->whereKey($affiliation->course_id)->exists();
        $students = $canRegisterForStudents
            ? Affiliation::query()
                ->active()
                ->where('type', AffiliationType::Student)
                ->where('course_id', $affiliation->course_id)
                ->with('user:id,name')
                ->orderBy('registration_number')
                ->get()
            : collect();

        return view('submissions.index', compact('submissions', 'affiliation', 'canCreateOwn', 'canRegisterForStudents', 'students'));
    }

    /**
     * Show the student form that creates a submission for the selected affiliation only.
     */
    public function create(Request $request): View
    {
        $affiliation = $this->activeAffiliation($request);
        $this->authorize('create', AccSubmission::class);
        $affiliation->load('user');

        return view('submissions.create', [
            'studentAffiliation' => $affiliation,
            'isCoordinatorSubmission' => false,
        ]);
    }

    /**
     * Create a submission whose beneficiary and author are the selected student affiliation.
     */
    public function store(StoreAccSubmissionRequest $request): RedirectResponse
    {
        $affiliation = $this->activeAffiliation($request);
        $submission = $this->createSubmission->execute(
            studentAffiliation: $affiliation,
            submittedByAffiliation: $affiliation,
            origin: SubmissionOrigin::Student,
            document: $request->file('document'),
            causer: $request->user(),
        );

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Comprovante enviado para análise.');
    }

    /**
     * Show the coordinator form for a student in the current course only.
     */
    public function createFor(Request $request, Affiliation $studentAffiliation): View
    {
        $this->authorize('createFor', [AccSubmission::class, $studentAffiliation]);
        $studentAffiliation->load('user');

        return view('submissions.create', [
            'studentAffiliation' => $studentAffiliation,
            'isCoordinatorSubmission' => true,
        ]);
    }

    /**
     * Record a proof submitted directly by the coordinator for the selected student.
     */
    public function storeFor(
        StoreCoordinatorAccSubmissionRequest $request,
        Affiliation $studentAffiliation,
    ): RedirectResponse {
        $submission = $this->createSubmission->execute(
            studentAffiliation: $studentAffiliation,
            submittedByAffiliation: $this->activeAffiliation($request),
            origin: SubmissionOrigin::Coordinator,
            document: $request->file('document'),
            causer: $request->user(),
        );

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Comprovante registrado para o discente.');
    }

    /**
     * Display a submission after verifying its beneficiary or course scope.
     */
    public function show(Request $request, AccSubmission $submission): View
    {
        $submission->load([
            'studentAffiliation.user',
            'studentAffiliation.course',
            'submittedByAffiliation.user',
            'media',
        ]);
        $this->authorize('view', $submission);

        return view('submissions.show', compact('submission'));
    }

    /**
     * Stream the proof inline from the private disk through the submission policy.
     */
    public function document(Request $request, AccSubmission $submission): StreamedResponse
    {
        $submission->load(['studentAffiliation.course', 'submittedByAffiliation']);
        $this->authorize('viewDocument', $submission);
        $media = $submission->getFirstMedia(AccSubmission::EvidenceCollection);

        abort_if($media === null, 404);
        $this->recordDocumentAccess($submission, $request, AuditEvent::SubmissionViewed);

        return $media->toInlineResponse($request);
    }

    /**
     * Download the proof from the private disk through the submission policy.
     */
    public function download(Request $request, AccSubmission $submission): StreamedResponse
    {
        $submission->load(['studentAffiliation.course', 'submittedByAffiliation']);
        $this->authorize('download', $submission);
        $media = $submission->getFirstMedia(AccSubmission::EvidenceCollection);

        abort_if($media === null, 404);
        $this->recordDocumentAccess($submission, $request, AuditEvent::SubmissionDownloaded);

        return $media->toResponse($request);
    }

    private function activeAffiliation(Request $request): Affiliation
    {
        $affiliation = $this->activeAffiliationContext->for($request->user());

        abort_if($affiliation === null, 403);

        return $affiliation;
    }

    private function recordDocumentAccess(
        AccSubmission $submission,
        Request $request,
        AuditEvent $event,
    ): void {
        $affiliation = $this->activeAffiliation($request);
        $course = $submission->studentAffiliation->course;

        $this->recordActivity->execute(
            event: $event,
            subject: $submission,
            causer: $request->user(),
            activeAffiliation: $affiliation,
            contextCourseId: $course->getKey(),
            references: [
                'course' => $course,
                'student_affiliation' => $submission->studentAffiliation,
                'submitted_by_affiliation' => $submission->submittedByAffiliation,
            ],
        );
    }
}
