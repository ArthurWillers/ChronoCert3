<?php

namespace App\Actions\Statements;

use App\Enums\AffiliationType;
use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildAccCategoryTotals
{
    /** @param array<int, int> $studentIds
     * @return Collection<int, \stdClass>
     */
    public function forStudents(array $studentIds): Collection
    {
        return $this->query()->whereIn('submissions.student_affiliation_id', $studentIds)->get();
    }

    public function recognizedHoursForCategory(int $studentId, int $categoryId): float
    {
        return (float) ($this->query()
            ->where('submissions.student_affiliation_id', $studentId)
            ->where('reviews.acc_category_id', $categoryId)
            ->first()?->recognized_hours ?? 0);
    }

    public function totalForCourse(int $courseId): float
    {
        $students = Affiliation::query()->active()
            ->where('type', AffiliationType::Student)
            ->where('course_id', $courseId)
            ->select('id');

        return (float) DB::query()
            ->fromSub($this->query()->whereIn('submissions.student_affiliation_id', $students), 'category_totals')
            ->sum('recognized_hours');
    }

    private function query(): Builder
    {
        $allocatedHours = "COALESCE(NULLIF(reviews.rules_snapshot->>'recognized_hours', '')::numeric, 0)";
        $legacyHours = "CASE WHEN reviews.rules_snapshot->>'recognized_hours' IS NULL THEN reviews.certificate_hours ELSE 0 END";

        return DB::table((new AccReview)->getTable().' as reviews')
            ->join((new AccSubmission)->getTable().' as submissions', 'submissions.id', '=', 'reviews.acc_submission_id')
            ->join((new AccCategory)->getTable().' as categories', 'categories.id', '=', 'reviews.acc_category_id')
            ->where('submissions.status', SubmissionStatus::Approved->value)
            ->select('submissions.student_affiliation_id', 'reviews.acc_category_id')
            ->selectRaw('COUNT(*) as accepted_documents_count, SUM(reviews.certificate_hours) as certificate_hours')
            ->selectRaw('SUM(CASE WHEN reviews.is_area_related THEN reviews.certificate_hours ELSE 0 END) as area_certificate_hours')
            ->selectRaw("GREATEST(SUM({$allocatedHours}), LEAST(SUM({$allocatedHours}) + SUM({$legacyHours}), categories.max_hours)) as recognized_hours")
            ->groupBy('submissions.student_affiliation_id', 'reviews.acc_category_id', 'categories.max_hours');
    }
}
