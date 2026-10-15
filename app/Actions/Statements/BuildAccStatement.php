<?php

namespace App\Actions\Statements;

use App\Enums\AffiliationType;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use Illuminate\Validation\ValidationException;

class BuildAccStatement
{
    public function __construct(
        private BuildStudentAccSummary $buildStudentAccSummary,
        private BuildAccCategoryTotals $buildAccCategoryTotals,
    ) {}

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
            ->latest('id')
            ->paginate(20);
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
            ->with('user:id,name')
            ->orderBy('registration_number')
            ->orderBy('id')
            ->paginate(20);
        $totals = $this->buildAccCategoryTotals->forStudents($students->getCollection()->modelKeys())->groupBy('student_affiliation_id');
        $students->through(fn (Affiliation $student): array => [
            'affiliation' => $student,
            'certificate_hours' => (float) $totals->get($student->getKey(), collect())->sum('recognized_hours'),
        ]);

        return [
            'mode' => 'coordinator',
            'affiliation' => $coordinatorAffiliation->loadMissing('course'),
            'students' => $students,
            'totalCertificateHours' => $this->buildAccCategoryTotals->totalForCourse($coordinatorAffiliation->course_id),
        ];
    }
}
