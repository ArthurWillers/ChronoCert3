<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $recognizedByCategory = [];
        $reviews = DB::table('acc_reviews as reviews')
            ->join('acc_submissions as submissions', 'submissions.id', '=', 'reviews.acc_submission_id')
            ->join('acc_categories as categories', 'categories.id', '=', 'reviews.acc_category_id')
            ->where('submissions.status', 'approved')
            ->where('reviews.certificate_hours', '>', 0)
            ->select('reviews.id', 'reviews.acc_category_id', 'reviews.certificate_hours', 'reviews.approved_hours', 'reviews.rules_snapshot', 'reviews.category_snapshot', 'submissions.student_affiliation_id', 'categories.max_hours')
            ->orderBy('reviews.completed_at')->orderBy('reviews.id')
            ->lazy(500);

        foreach ($reviews as $review) {
            $key = $review->student_affiliation_id.':'.$review->acc_category_id;
            $rules = json_decode($review->rules_snapshot ?? '{}', true, flags: JSON_THROW_ON_ERROR);
            $category = json_decode($review->category_snapshot ?? '{}', true, flags: JSON_THROW_ON_ERROR);
            $previous = $recognizedByCategory[$key] ?? 0;

            if (isset($rules['recognized_hours'])) {
                $recognizedByCategory[$key] = $previous + (int) round((float) $rules['recognized_hours'] * 100);

                continue;
            }

            $certificateHours = max(0, (int) round((float) $review->certificate_hours * 100));
            $limit = (int) round((float) ($rules['category_max_hours'] ?? $category['max_hours'] ?? $review->max_hours) * 100);
            $recognized = $review->approved_hours !== null
                ? min($certificateHours, max(0, (int) round((float) $review->approved_hours * 100)))
                : min($certificateHours, max(0, $limit - $previous));
            $rules['recognized_hours'] = number_format($recognized / 100, 2, '.', '');
            DB::table('acc_reviews')->where('id', $review->id)
                ->update(['rules_snapshot' => json_encode($rules, JSON_THROW_ON_ERROR)]);
            $recognizedByCategory[$key] = $previous + $recognized;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Academic allocations are preserved when rolling back this backfill.
    }
};
