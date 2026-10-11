<?php

namespace Database\Factories;

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Models\QuizQuestion;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'question_order' => 1,
            'question_text' => fake()->sentence().'?',
            'option_a' => fake()->words(3, true),
            'option_b' => fake()->words(3, true),
            'option_c' => fake()->words(3, true),
            'option_d' => fake()->words(3, true),
            'correct_option' => AnswerOption::A,
            'curriculum_context' => fake()->sentence(),
            'status' => QuestionStatus::Active,
        ];
    }
}
