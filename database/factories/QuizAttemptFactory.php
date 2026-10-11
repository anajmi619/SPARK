<?php

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student()->active(),
            'topic_id' => Topic::factory(),
            'attempt_number' => 1,
            'score' => null,
            'status' => AttemptStatus::InProgress,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function completed(?int $score = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'score' => $score ?? fake()->numberBetween(0, 10),
            'status' => AttemptStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
