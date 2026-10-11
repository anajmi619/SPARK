<?php

namespace App\Actions\Teacher;

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Models\QuizQuestion;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateQuizQuestion
{
    /**
     * Add the next question to a topic's ten-question quiz.
     *
     * @param  array{a: string, b: string, c: string, d: string}  $answerOptions
     */
    public function handle(
        Topic $topic,
        string $questionText,
        array $answerOptions,
        AnswerOption $correctOption,
        ?string $curriculumContext,
        QuestionStatus $status,
        ?string $explanation = null,
    ): QuizQuestion {
        return DB::transaction(function () use ($topic, $questionText, $answerOptions, $correctOption, $curriculumContext, $status, $explanation): QuizQuestion {
            Topic::query()
                ->whereKey($topic->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $questions = QuizQuestion::query()
                ->where('topic_id', $topic->topic_id)
                ->lockForUpdate()
                ->orderBy('question_order')
                ->get();

            if ($questions->count() >= 10) {
                throw ValidationException::withMessages([
                    'question_text' => ['Maksimum 10 soalan kuiz telah ditetapkan untuk topik ini.'],
                ]);
            }

            $nextQuestionOrder = $questions->isEmpty()
                ? 1
                : $questions->last()->question_order + 1;

            return QuizQuestion::query()->create([
                'topic_id' => $topic->topic_id,
                'question_order' => $nextQuestionOrder,
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
        });
    }
}
