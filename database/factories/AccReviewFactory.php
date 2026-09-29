<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccReview>
 */
class AccReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'acc_submission_id' => AccSubmission::factory()->state([
                'status' => SubmissionStatus::UnderReview,
                'review_started_at' => now(),
            ]),
            'reviewer_affiliation_id' => Affiliation::factory()->coordinator(),
            'acc_category_id' => null,
            'original_title' => fake()->sentence(3),
            'normalized_title' => null,
            'certificate_hours' => null,
            'is_area_related' => false,
            'classification_justification' => null,
            'rejection_reason' => null,
            'category_snapshot' => null,
            'rules_snapshot' => null,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function classified(): static
    {
        return $this->state(fn (): array => [
            'acc_category_id' => AccCategory::factory(),
            'normalized_title' => fake()->sentence(3),
            'certificate_hours' => 10,
        ]);
    }
}
