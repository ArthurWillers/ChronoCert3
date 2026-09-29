<?php

namespace App\Actions\Dashboards;

use App\Actions\Statements\BuildStudentAccSummary;
use App\Enums\AffiliationType;
use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;

class BuildDashboard
{
    public function __construct(private BuildStudentAccSummary $buildStudentAccSummary) {}

    /** @return array<string, mixed> */
    public function execute(Affiliation $affiliation): array
    {
        return match ($affiliation->type) {
            AffiliationType::Administrator => $this->forAdministrator($affiliation),
            AffiliationType::Coordinator => $this->forCoordinator($affiliation),
            AffiliationType::Student => $this->forStudent($affiliation),
        };
    }

    /** @return array<string, mixed> */
    private function forAdministrator(Affiliation $affiliation): array
    {
        return [
            'affiliation' => $affiliation,
            'metrics' => [
                'active_courses' => Course::query()->active()->count(),
                'active_students' => Affiliation::query()->active()->where('type', AffiliationType::Student)->count(),
                'active_coordinators' => Affiliation::query()->active()->where('type', AffiliationType::Coordinator)->count(),
                'pending_reviews' => AccSubmission::query()
                    ->whereIn('status', [SubmissionStatus::Submitted, SubmissionStatus::UnderReview])
                    ->count(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function forCoordinator(Affiliation $affiliation): array
    {
        $submissions = AccSubmission::query()->visibleTo($affiliation);

        return [
            'affiliation' => $affiliation,
            'submissionCounts' => $this->submissionStatusCounts($submissions),
            'activeStudentsCount' => Affiliation::query()
                ->active()
                ->where('type', AffiliationType::Student)
                ->where('course_id', $affiliation->course_id)
                ->count(),
            'activeCategoriesCount' => AccCategory::query()
                ->active()
                ->where('course_id', $affiliation->course_id)
                ->count(),
            'pendingSubmissions' => (clone $submissions)
                ->where('status', SubmissionStatus::Submitted)
                ->with(['studentAffiliation.user:id,name', 'media'])
                ->latest('submitted_at')
                ->latest('id')
                ->limit(5)
                ->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function forStudent(Affiliation $affiliation): array
    {
        $submissions = AccSubmission::query()->whereBelongsTo($affiliation, 'studentAffiliation');

        return [
            'affiliation' => $affiliation,
            'summary' => $this->buildStudentAccSummary->execute($affiliation),
            'submissionCounts' => $this->submissionStatusCounts($submissions),
            'recentSubmissions' => $submissions
                ->with(['review.category', 'media'])
                ->latest('submitted_at')
                ->latest('id')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @param  Builder<AccSubmission>  $submissions
     * @return array{submitted: int, under_review: int, rejected: int, approved: int}
     */
    private function submissionStatusCounts(Builder $submissions): array
    {
        $counts = (clone $submissions)
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as submitted', [SubmissionStatus::Submitted->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as under_review', [SubmissionStatus::UnderReview->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as rejected', [SubmissionStatus::Rejected->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as approved', [SubmissionStatus::Approved->value])
            ->first();

        return [
            'submitted' => (int) ($counts->submitted ?? 0),
            'under_review' => (int) ($counts->under_review ?? 0),
            'rejected' => (int) ($counts->rejected ?? 0),
            'approved' => (int) ($counts->approved ?? 0),
        ];
    }
}
