<?php

namespace Database\Factories;

use App\Enums\AffiliationType;
use App\Models\AccCategory;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AccCategory> */
class AccCategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->sentence(),
            'max_hours' => fake()->numberBetween(10, 100),
            'guidance' => null,
            'created_by_affiliation_id' => fn (array $attributes): int => Affiliation::create([
                'user_id' => User::factory()->create()->getKey(),
                'course_id' => $attributes['course_id'],
                'type' => AffiliationType::Coordinator,
                'email' => fake()->safeEmail(),
            ])->getKey(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'deactivated_at' => now(),
            'deactivation_reason' => 'Categoria inativada para demonstração.',
        ]);
    }
}
