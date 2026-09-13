<?php

namespace App\Actions\Statements;

use App\Enums\AffiliationType;
use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BuildAccStatement
{
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
        $approvedReviews = $history
            ->where('status', SubmissionStatus::Approved)
            ->pluck('review')
            ->filter(fn (mixed $review): bool => $review instanceof AccReview);

        return [
            'mode' => 'student',
            'affiliation' => $studentAffiliation->loadMissing(['user', 'course']),
            'history' => $history,
            'categorySummaries' => $this->categorySummaries($studentAffiliation, $approvedReviews),
            'totalApprovedHours' => $approvedReviews->sum(fn (AccReview $review): float => (float) $review->approved_hours),
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
                    ->with('review'),
            ])
            ->orderBy('registration_number')
            ->get()
            ->map(function (Affiliation $student): array {
                $approvedHours = $student->submissionsAsStudent
                    ->pluck('review')
                    ->filter(fn (mixed $review): bool => $review instanceof AccReview)
                    ->sum(fn (AccReview $review): float => (float) $review->approved_hours);

                return ['affiliation' => $student, 'approved_hours' => $approvedHours];
            });

        return [
            'mode' => 'coordinator',
            'affiliation' => $coordinatorAffiliation->loadMissing('course'),
            'students' => $students,
            'totalApprovedHours' => $students->sum('approved_hours'),
        ];
    }

    /**
     * @param  Collection<int, AccReview>  $approvedReviews
     * @return Collection<int, array<string, mixed>>
     */
    private function categorySummaries(Affiliation $studentAffiliation, Collection $approvedReviews): Collection
    {
        return AccCategory::query()
            ->where('course_id', $studentAffiliation->course_id)
            ->orderBy('name')
            ->get()
            ->map(function (AccCategory $category) use ($approvedReviews): array {
                $approvedHours = $approvedReviews
                    ->where('acc_category_id', $category->getKey())
                    ->sum(fn (AccReview $review): float => (float) $review->approved_hours);

                return [
                    'category' => $category,
                    'approved_hours' => $approvedHours,
                    'available_hours' => max(0, (float) $category->max_hours - $approvedHours),
                ];
            });
    }
}
