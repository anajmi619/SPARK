<?php

namespace Database\Factories;

use App\Enums\AiFeedbackStatus;
use App\Enums\AnswerOption;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAnswer>
 */
class QuizAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attempt_id' => QuizAttempt::factory(),
            'question_id' => QuizQuestion::factory(),
            'selected_option' => AnswerOption::A,
            'is_correct' => true,
            'question_snapshot' => fake()->sentence().'?',
            'selected_answer_snapshot' => fake()->words(3, true),
            'correct_answer_snapshot' => fake()->words(3, true),
            'ai_feedback_text' => null,
            'ai_feedback_status' => AiFeedbackStatus::NotRequired,
            'answered_at' => now(),
        ];
    }

    public function incorrectWithFeedback(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_correct' => false,
            'ai_feedback_text' => fake()->sentence(),
            'ai_feedback_status' => AiFeedbackStatus::Generated,
        ]);
    }
}
