<?php

namespace App\Actions\Statements;

use App\Models\AccCategory;
use App\Models\Affiliation;
use Illuminate\Support\Collection;

class BuildStudentAccSummary
{
    public function __construct(private BuildAccCategoryTotals $buildAccCategoryTotals) {}

    /**
     * @return array{categorySummaries: Collection<int, array{category: AccCategory, accepted_documents_count: int, certificate_hours: float, recognized_hours: float, recognized_area_hours: float}>, acceptedDocumentsCount: int, totalCertificateHours: float, recognizedHours: float, recognizedAreaHours: float, minimumAreaHours: ?float}
     */
    public function execute(Affiliation $studentAffiliation): array
    {
        $studentAffiliation->loadMissing('course');

        $totals = $this->buildAccCategoryTotals->forStudents([$studentAffiliation->getKey()])->keyBy('acc_category_id');

        $categorySummaries = AccCategory::query()
            ->where('course_id', $studentAffiliation->course_id)
            ->orderBy('name')
            ->get()
            ->map(function (AccCategory $category) use ($totals): array {
                $categoryTotals = $totals->get($category->getKey());
                $certificateHours = (float) ($categoryTotals?->certificate_hours ?? 0);
                $recognizedHours = (float) ($categoryTotals?->recognized_hours ?? 0);

                return [
                    'category' => $category,
                    'accepted_documents_count' => (int) ($categoryTotals?->accepted_documents_count ?? 0),
                    'certificate_hours' => $certificateHours,
                    'recognized_hours' => $recognizedHours,
                    'recognized_area_hours' => min((float) ($categoryTotals?->area_certificate_hours ?? 0), $recognizedHours),
                ];
            });

        $minimumAreaHours = $studentAffiliation->course?->minimumAreaHours();
        $recognizedAreaHours = (float) $categorySummaries->sum('recognized_area_hours');

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
