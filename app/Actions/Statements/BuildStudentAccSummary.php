<?php

namespace App\Actions\Statements;

use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\Affiliation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildStudentAccSummary
{
    /**
     * @return array{categorySummaries: Collection<int, array{category: AccCategory, accepted_documents_count: int, certificate_hours: float, recognized_hours: float, recognized_area_hours: float}>, acceptedDocumentsCount: int, totalCertificateHours: float, recognizedHours: float, recognizedAreaHours: float, minimumAreaHours: ?float}
     */
    public function execute(Affiliation $studentAffiliation): array
    {
        $studentAffiliation->loadMissing('course');

        $acceptedReviews = function (Builder $query) use ($studentAffiliation): Builder {
            return $query->whereHas('submission', function (Builder $submissions) use ($studentAffiliation): Builder {
                return $submissions
                    ->whereBelongsTo($studentAffiliation, 'studentAffiliation')
                    ->where('status', SubmissionStatus::Approved);
            });
        };

        $categorySummaries = AccCategory::query()
            ->where('course_id', $studentAffiliation->course_id)
            ->orderBy('name')
            ->withCount(['reviews as accepted_documents_count' => $acceptedReviews])
            ->withSum(['reviews as accepted_certificate_hours' => $acceptedReviews], 'certificate_hours')
            ->withSum([
                'reviews as area_certificate_hours' => fn (Builder $query): Builder => $acceptedReviews($query)
                    ->where('is_area_related', true),
            ], 'certificate_hours')
            ->get()
            ->map(function (AccCategory $category): array {
                $certificateHours = (float) ($category->accepted_certificate_hours ?? 0);
                $recognizedHours = min($certificateHours, (float) $category->max_hours);

                return [
                    'category' => $category,
                    'accepted_documents_count' => (int) $category->accepted_documents_count,
                    'certificate_hours' => $certificateHours,
                    'recognized_hours' => $recognizedHours,
                    'recognized_area_hours' => min((float) ($category->area_certificate_hours ?? 0), $recognizedHours),
                ];
            });

        $minimumAreaHours = $studentAffiliation->course?->minimumAreaHours();
        $recognizedAreaHours = (float) $categorySummaries->sum('recognized_area_hours');

        if ($minimumAreaHours !== null) {
            $recognizedAreaHours = min($recognizedAreaHours, $minimumAreaHours);
        }

        return [
            'categorySummaries' => $categorySummaries,
            'acceptedDocumentsCount' => $categorySummaries->sum('accepted_documents_count'),
            'totalCertificateHours' => $categorySummaries->sum('certificate_hours'),
            'recognizedHours' => $categorySummaries->sum('recognized_hours'),
            'recognizedAreaHours' => $recognizedAreaHours,
            'minimumAreaHours' => $minimumAreaHours,
        ];
    }
}
