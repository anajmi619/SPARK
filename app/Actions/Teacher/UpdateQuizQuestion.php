<?php

namespace App\Actions\Teacher;

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Models\QuizQuestion;

class UpdateQuizQuestion
{
    /**
     * Update a quiz question while retaining its place in the topic sequence.
     *
     * @param  array{a: string, b: string, c: string, d: string}  $answerOptions
     */
    public function handle(
        QuizQuestion $question,
        string $questionText,
        array $answerOptions,
        AnswerOption $correctOption,
        ?string $curriculumContext,
        QuestionStatus $status,
        ?string $explanation = null,
    ): QuizQuestion {
        $question = QuizQuestion::query()->whereKey($question->getKey())->lockForUpdate()->firstOrFail();
        $question->update([
            'question_text' => $questionText,
            'option_a' => $answerOptions['a'],
            'option_b' => $answerOptions['b'],
            'option_c' => $answerOptions['c'],
            'option_d' => $answerOptions['d'],
            'correct_option' => $correctOption,
            'curriculum_context' => $curriculumContext,
            'status' => $status,
            'explanation' => $explanation,
        ]);

        return $question->refresh();
    }
}
