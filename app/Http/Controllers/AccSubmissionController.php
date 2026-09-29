<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Audit\RecordActivity;
use App\Actions\Reviews\StartAccReview;
use App\Actions\Submissions\CreateSubmission;
use App\Enums\AffiliationType;
use App\Enums\AuditEvent;
use App\Enums\SubmissionOrigin;
use App\Http\Requests\IndexAccSubmissionRequest;
use App\Http\Requests\StoreAccSubmissionRequest;
use App\Http\Requests\StoreCoordinatorAccSubmissionRequest;
use App\Models\AccCategory;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

class AccSubmissionController extends Controller
{
    public function __construct(
        private ActiveAffiliationContext $activeAffiliationContext,
        private CreateSubmission $createSubmission,
        private StartAccReview $startAccReview,
        private RecordActivity $recordActivity,
    ) {}

    /**
     * Display submissions in the scope allowed by the selected affiliation.
     */
    public function index(IndexAccSubmissionRequest $request): View
    {
        $affiliation = $this->activeAffiliation($request);
        $this->authorize('viewOwnList', AccSubmission::class);

        return $this->renderIndex($request, $affiliation, $affiliation);
    }

    public function indexFor(IndexAccSubmissionRequest $request, Affiliation $studentAffiliation): View
    {
        $this->authorize('viewForStudent', [AccSubmission::class, $studentAffiliation]);
        $studentAffiliation->load(['user', 'course']);

        return $this->renderIndex($request, $studentAffiliation, $this->activeAffiliation($request));
    }

    public function downloadAll(IndexAccSubmissionRequest $request): StreamedResponse
    {
        $this->authorize('viewOwnList', AccSubmission::class);
        $affiliation = $this->activeAffiliation($request);

        return $this->archive($request, $affiliation);
    }

    public function downloadAllFor(IndexAccSubmissionRequest $request, Affiliation $studentAffiliation): StreamedResponse
    {
        $this->authorize('viewForStudent', [AccSubmission::class, $studentAffiliation]);

        return $this->archive($request, $studentAffiliation);
    }

    private function renderIndex(IndexAccSubmissionRequest $request, Affiliation $studentAffiliation, Affiliation $activeAffiliation): View
    {
        $submissions = $this->studentSubmissions($studentAffiliation, $request->validated())
            ->with([
                'submittedByAffiliation.user:id,name',
                'review.category',
                'media',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
        $categories = AccCategory::query()
            ->where('course_id', $studentAffiliation->course_id)
            ->orderBy('name')
            ->get();
        $archiveHasFiles = $this->studentSubmissions($studentAffiliation, $request->validated())
            ->whereHas('media', fn (Builder $media): Builder => $media->where('collection_name', AccSubmission::EvidenceCollection))
            ->exists();
        $isCoordinator = $activeAffiliation->type === AffiliationType::Coordinator;
        $canCreateOwn = ! $isCoordinator && $request->user()->can('create', AccSubmission::class);
        $canCreateFor = $isCoordinator && $request->user()->can('createFor', [AccSubmission::class, $studentAffiliation]);

        return view('submissions.index', compact('submissions', 'studentAffiliation', 'categories', 'isCoordinator', 'canCreateOwn', 'canCreateFor', 'archiveHasFiles'));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AccSubmission>
     */
    private function studentSubmissions(Affiliation $studentAffiliation, array $filters): Builder
    {
        return AccSubmission::query()
            ->where('student_affiliation_id', $studentAffiliation->getKey())
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status),
            )
            ->when(
                $filters['acc_category_id'] ?? null,
                fn (Builder $query, int $categoryId): Builder => $query
                    ->whereHas('review', fn (Builder $review): Builder => $review->where('acc_category_id', $categoryId)),
            );
    }

    private function archive(IndexAccSubmissionRequest $request, Affiliation $studentAffiliation): StreamedResponse
    {
        $submissions = $this->studentSubmissions($studentAffiliation, $request->validated())
            ->with(['media', 'studentAffiliation.course', 'submittedByAffiliation'])
            ->orderBy('id')
            ->get();
        $documents = $submissions
            ->map(fn (AccSubmission $submission): array => [
                'submission' => $submission,
                'media' => $submission->getFirstMedia(AccSubmission::EvidenceCollection),
            ])
            ->filter(fn (array $document): bool => $document['media'] instanceof Media)
            ->values();

        abort_if($documents->isEmpty(), 404);

        foreach ($documents as $document) {
            $this->recordDocumentAccess($document['submission'], $request, AuditEvent::SubmissionExported);
        }

        $filename = 'comprovantes-matricula-'.Str::slug($studentAffiliation->registration_number ?: (string) $studentAffiliation->getKey()).'.zip';

        return response()->streamDownload(function () use ($documents, $filename): void {
            $zip = new ZipStream(outputName: $filename, sendHttpHeaders: false);

            foreach ($documents as $document) {
                $media = $document['media'];
                $originalName = basename(str_replace('\\', '/', (string) $media->getCustomProperty('original_filename', $media->file_name)));
                $safeName = preg_replace('/[\x00-\x1f\x7f]/', '', $originalName) ?: $media->file_name;
                $stream = $media->stream();

                try {
                    $zip->addFileFromStream('documento-'.$document['submission']->getKey().'/'.$safeName, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            }

            $zip->finish();
        }, $filename, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
        ]);
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
            ->with('success', 'Documento enviado para análise.');
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
        $this->startAccReview->execute(
            submission: $submission,
            reviewerAffiliation: $this->activeAffiliation($request),
            causer: $request->user(),
        );

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Documento registrado e pronto para revisão.');
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
            'review.category',
            'review.reviewerAffiliation.user',
            'media',
        ]);
        $this->authorize('view', $submission);
        $affiliation = $this->activeAffiliation($request);
        $categories = $affiliation->type === AffiliationType::Coordinator
            ? AccCategory::query()->active()->visibleTo($affiliation)->orderBy('name')->get()
            : collect();
        $canStartReview = $request->user()->can('create', [AccReview::class, $submission]);
        $canUpdateReview = $submission->review !== null
            && $request->user()->can('update', $submission->review);
        $isCoordinator = $affiliation->type === AffiliationType::Coordinator;

        return view('submissions.show', compact(
            'submission',
            'categories',
            'canStartReview',
            'canUpdateReview',
            'isCoordinator',
        ));
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
