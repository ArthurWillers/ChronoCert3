<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Users\PreviewStudentAffiliationCsv;
use App\Actions\Users\ProcessStudentAffiliationCsvImport;
use App\Http\Requests\ConfirmStudentAffiliationImportRequest;
use App\Http\Requests\PreviewStudentAffiliationImportRequest;
use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentAffiliationImportController extends Controller
{
    public function __construct(
        private ActiveAffiliationContext $activeAffiliationContext,
        private PreviewStudentAffiliationCsv $previewStudentAffiliationCsv,
        private ProcessStudentAffiliationCsvImport $processStudentAffiliationCsvImport,
    ) {}

    public function create(Request $request): View
    {
        $this->authorize('importStudents', User::class);
        $activeAffiliation = $this->activeAffiliation($request);

        return view('users.import.create', [
            'activeAffiliation' => $activeAffiliation->loadMissing('course'),
        ]);
    }

    public function template(Request $request): StreamedResponse
    {
        $this->authorize('importStudents', User::class);

        return response()->streamDownload(
            static function (): void {
                echo "\xEF\xBB\xBFcpf;nome;email;matricula\r\n";
            },
            'modelo-cadastro-em-massa.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function preview(PreviewStudentAffiliationImportRequest $request): View
    {
        $activeAffiliation = $this->activeAffiliation($request);
        $preview = $this->previewStudentAffiliationCsv->execute(
            $request->file('csv_file'),
            $activeAffiliation,
        );
        $token = Str::random(64);

        Cache::put($this->previewCacheKey($token), [
            'user_id' => $request->user()->getKey(),
            'affiliation_id' => $activeAffiliation->getKey(),
            'course_id' => $activeAffiliation->course_id,
            'rows' => $preview['rows'],
        ], now()->addMinutes(30));

        return view('users.import.preview', [
            'activeAffiliation' => $activeAffiliation->loadMissing('course'),
            'rows' => $preview['rows'],
            'summary' => $preview['summary'],
            'token' => $token,
        ]);
    }

    public function confirm(ConfirmStudentAffiliationImportRequest $request): RedirectResponse
    {
        $activeAffiliation = $this->activeAffiliation($request);
        $token = $request->validated('token');
        $previewKey = $this->previewCacheKey($token);
        $lock = Cache::lock($this->lockCacheKey($token), 600);

        abort_unless($lock->get(), 409, 'Este arquivo já está sendo processado.');

        try {
            $payload = Cache::pull($previewKey);

            if (! is_array($payload)) {
                throw ValidationException::withMessages(['token' => 'A prévia expirou ou já foi confirmada. Envie o arquivo novamente.']);
            }

            $this->ensurePayloadBelongsTo($payload, $request->user(), $activeAffiliation);
            $result = $this->processStudentAffiliationCsvImport->execute(
                rows: $payload['rows'],
                causer: $request->user(),
                activeAffiliation: $activeAffiliation,
            );
            $reportToken = Str::random(64);
            $reportRows = array_map(fn (array $row): array => [
                'number' => $row['number'],
                'name' => $row['name'],
                'cpf' => $this->maskedCpf($row['cpf']),
                'registration_number' => $row['registration_number'],
                'status' => $row['status'],
                'errors' => $row['errors'],
            ], $result['rows']);

            Cache::put($this->reportCacheKey($reportToken), [
                'user_id' => $request->user()->getKey(),
                'affiliation_id' => $activeAffiliation->getKey(),
                'course_id' => $activeAffiliation->course_id,
                'rows' => $reportRows,
                'summary' => $result['summary'],
            ], now()->addMinutes(30));
        } finally {
            $lock->release();
        }

        return redirect()->route('users.import.report', $reportToken);
    }

    public function report(Request $request, string $report): View
    {
        $this->authorize('importStudents', User::class);

        if (preg_match('/^[A-Za-z0-9]{64}$/', $report) !== 1) {
            abort(404);
        }

        $activeAffiliation = $this->activeAffiliation($request);
        $payload = Cache::get($this->reportCacheKey($report));

        if (! is_array($payload)) {
            abort(404);
        }

        $this->ensurePayloadBelongsTo($payload, $request->user(), $activeAffiliation);

        return view('users.import.report', [
            'activeAffiliation' => $activeAffiliation->loadMissing('course'),
            'rows' => $payload['rows'],
            'summary' => $payload['summary'],
        ]);
    }

    private function activeAffiliation(Request $request): Affiliation
    {
        $affiliation = $this->activeAffiliationContext->for($request->user());

        abort_if($affiliation === null, 403);

        return $affiliation;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ensurePayloadBelongsTo(array $payload, User $user, Affiliation $activeAffiliation): void
    {
        abort_unless(
            (int) ($payload['user_id'] ?? 0) === (int) $user->getKey()
                && (int) ($payload['affiliation_id'] ?? 0) === (int) $activeAffiliation->getKey()
                && (int) ($payload['course_id'] ?? 0) === (int) $activeAffiliation->course_id,
            404,
        );
    }

    private function previewCacheKey(string $token): string
    {
        return 'student-affiliation-import.preview.'.hash('sha256', $token);
    }

    private function reportCacheKey(string $token): string
    {
        return 'student-affiliation-import.report.'.hash('sha256', $token);
    }

    private function lockCacheKey(string $token): string
    {
        return 'student-affiliation-import.lock.'.hash('sha256', $token);
    }

    private function maskedCpf(string $cpf): string
    {
        return strlen($cpf) === 11
            ? substr($cpf, 0, 3).'.***.**'.substr($cpf, -2)
            : '—';
    }
}
