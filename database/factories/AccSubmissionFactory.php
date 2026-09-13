<?php

namespace Database\Factories;

use App\Enums\SubmissionOrigin;
use App\Enums\SubmissionStatus;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccSubmission>
 */
class AccSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_affiliation_id' => Affiliation::factory()->student(),
            'submitted_by_affiliation_id' => fn (array $attributes): int => $attributes['student_affiliation_id'],
            'origin' => SubmissionOrigin::Student,
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now(),
            'review_started_at' => null,
            'reviewed_at' => null,
            'rejected_at' => null,
            'purge_at' => null,
        ];
    }
}
