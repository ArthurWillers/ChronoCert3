<?php

namespace App\Actions\Statements;

use App\Enums\AffiliationType;
use App\Enums\SubmissionStatus;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class BuildAccStatement
{
    public function __construct(private BuildStudentAccSummary $buildStudentAccSummary) {}

    /** @return array<string, mixed> */
    public function execute(Affiliation $affiliation): array
    {
        if (! $affiliation->isActive() || $affiliation->course_id === null) {
            throw ValidationException::withMessages(['affiliation' => 'Selecione um vínculo ativo com curso.']);
        }

        return match ($affiliation->type) {
            AffiliationType::Student => $this->forStudent($affiliation),
            AffiliationType::Coordinator => $this->forCourse($affiliation),
            default => throw ValidationException::withMessages(['affiliation' => 'Este vínculo não possui extrato acadêmico.']),
        };
    }

    /** @return array<string, mixed> */
    private function forStudent(Affiliation $studentAffiliation): array
    {
        $history = AccSubmission::query()
            ->whereBelongsTo($studentAffiliation, 'studentAffiliation')
            ->with(['review.category', 'media'])
            ->latest('submitted_at')
            ->get();
        $summary = $this->buildStudentAccSummary->execute($studentAffiliation);

        return [
            'mode' => 'student',
            'affiliation' => $studentAffiliation->loadMissing(['user', 'course']),
            'history' => $history,
            ...$summary,
        ];
    }

    /** @return array<string, mixed> */
    private function forCourse(Affiliation $coordinatorAffiliation): array
    {
        $students = Affiliation::query()
            ->active()
            ->where('type', AffiliationType::Student)
            ->where('course_id', $coordinatorAffiliation->course_id)
            ->with([
                'user:id,name',
                'submissionsAsStudent' => fn (HasMany $query): HasMany => $query
                    ->where('status', SubmissionStatus::Approved)
                    ->with('review.category'),
            ])
            ->orderBy('registration_number')
            ->get()
            ->map(function (Affiliation $student): array {
                $certificateHours = $student->submissionsAsStudent
                    ->pluck('review')
                    ->filter(fn (mixed $review): bool => $review instanceof AccReview)
                    ->groupBy('acc_category_id')
                    ->sum(function ($reviews): float {
                        $category = $reviews->first()?->category;

                        return min(
                            (float) $reviews->sum(fn (AccReview $review): float => (float) $review->certificate_hours),
                            (float) $category?->max_hours,
                        );
                    });

                return ['affiliation' => $student, 'certificate_hours' => $certificateHours];
            });

        return [
            'mode' => 'coordinator',
            'affiliation' => $coordinatorAffiliation->loadMissing('course'),
            'students' => $students,
            'totalCertificateHours' => $students->sum('certificate_hours'),
        ];
    }
}
