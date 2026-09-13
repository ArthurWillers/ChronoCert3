<?php

namespace Database\Factories;

use App\Enums\AffiliationType;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Affiliation>
 */
class AffiliationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'type' => AffiliationType::Student,
            'email' => fake()->unique()->safeEmail(),
            'registration_number' => fake()->unique()->numerify('########'),
        ];
    }

    public function student(): static
    {
        return $this->state(fn (): array => [
            'type' => AffiliationType::Student,
        ]);
    }

    public function coordinator(): static
    {
        return $this->state(fn (): array => [
            'type' => AffiliationType::Coordinator,
            'registration_number' => null,
        ]);
    }

    public function administrator(): static
    {
        return $this->state(fn (): array => [
            'course_id' => null,
            'type' => AffiliationType::Administrator,
            'registration_number' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'deactivated_at' => now(),
        ]);
    }
}
