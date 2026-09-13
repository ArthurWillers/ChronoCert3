<?php

namespace App\Actions\Submissions;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Enums\SubmissionOrigin;
use App\Enums\SubmissionStatus;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSubmission
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * Persist one proof submission and its private media item.
     */
    public function execute(
        Affiliation $studentAffiliation,
        Affiliation $submittedByAffiliation,
        SubmissionOrigin $origin,
        UploadedFile $document,
        User $causer,
    ): AccSubmission {
        return DB::transaction(function () use ($studentAffiliation, $submittedByAffiliation, $origin, $document, $causer): AccSubmission {
            $studentAffiliation = Affiliation::query()
                ->with('course')
                ->lockForUpdate()
                ->findOrFail($studentAffiliation->getKey());
            $submittedByAffiliation = Affiliation::query()
                ->lockForUpdate()
                ->findOrFail($submittedByAffiliation->getKey());
            $course = Course::query()->lockForUpdate()->findOrFail($studentAffiliation->course_id);

            if ($origin === SubmissionOrigin::Student) {
                Gate::forUser($causer)->authorize('create', AccSubmission::class);
            } else {
                Gate::forUser($causer)->authorize('createFor', [AccSubmission::class, $studentAffiliation]);
            }

            $this->ensureCompatibleAffiliations($studentAffiliation, $submittedByAffiliation, $course, $origin);

            $submission = AccSubmission::create([
                'student_affiliation_id' => $studentAffiliation->getKey(),
                'submitted_by_affiliation_id' => $submittedByAffiliation->getKey(),
                'origin' => $origin,
                'status' => SubmissionStatus::Submitted,
                'submitted_at' => now(),
            ]);

            $extension = Str::lower($document->getClientOriginalExtension());
            $temporaryPath = $document->getRealPath();
            $fileHash = $temporaryPath === false ? false : hash_file('sha256', $temporaryPath);
            $detectedMimeType = $temporaryPath === false
                ? null
                : (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
            $submission->addMedia($document)
                ->usingFileName(Str::uuid()->toString().'.'.$extension)
                ->withCustomProperties(array_filter([
                    'original_filename' => $document->getClientOriginalName(),
                    'detected_mime_type' => $detectedMimeType,
                    'size_bytes' => $document->getSize(),
                    'sha256' => $fileHash === false ? null : $fileHash,
                ], static fn (mixed $value): bool => $value !== null))
                ->toMediaCollection(AccSubmission::EvidenceCollection);

            $this->recordActivity->execute(
                event: $origin === SubmissionOrigin::Student
                    ? AuditEvent::SubmissionCreated
                    : AuditEvent::SubmissionUploadedByCoordinator,
                subject: $submission,
                causer: $causer,
                activeAffiliation: $submittedByAffiliation,
                contextCourseId: $course->getKey(),
                references: [
                    'course' => $course,
                    'student_affiliation' => $studentAffiliation,
                    'submitted_by_affiliation' => $submittedByAffiliation,
                ],
                changes: [
                    'origin' => ['old' => null, 'new' => $origin->value],
                    'status' => ['old' => null, 'new' => SubmissionStatus::Submitted->value],
                    'submitted_at' => ['old' => null, 'new' => $submission->submitted_at?->toIso8601String()],
                ],
            );

            return $submission;
        });
    }

    private function ensureCompatibleAffiliations(
        Affiliation $studentAffiliation,
        Affiliation $submittedByAffiliation,
        Course $course,
        SubmissionOrigin $origin,
    ): void {
        $studentIsValid = $studentAffiliation->isActive()
            && $studentAffiliation->type->value === 'student'
            && $studentAffiliation->course_id !== null
            && $course->deactivated_at === null;
        $submittedByIsValid = $submittedByAffiliation->isActive()
            && (int) $submittedByAffiliation->course_id === (int) $studentAffiliation->course_id
            && match ($origin) {
                SubmissionOrigin::Student => (int) $submittedByAffiliation->getKey() === (int) $studentAffiliation->getKey(),
                SubmissionOrigin::Coordinator => $submittedByAffiliation->type->value === 'coordinator',
            };

        if (! $studentIsValid || ! $submittedByIsValid) {
            throw ValidationException::withMessages([
                'student_affiliation_id' => 'O vínculo discente deve estar ativo e pertencer ao curso do envio.',
            ]);
        }
    }
}
